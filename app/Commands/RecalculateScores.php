<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * RecalculateScores
 *
 * Job CLI Spark para recalcular o Score Preditivo de todos os leads da base.
 *
 * Uso: php spark enrich:recalculate
 *
 * Lê os pesos da tabela scoring_config, aplica o fator de amortização nos
 * CNAEs secundários e grava o resultado em client_enrichment.logistics_score.
 */
class RecalculateScores extends BaseCommand
{
    protected $group       = 'Enrichment';
    protected $name        = 'enrich:recalculate';
    protected $description = 'Recalcula o Score Preditivo de prospecção logística para todos os CNPJs da base.';

    // Provedores de e-mail público a descontar
    private array $publicEmailDomains = [
        'gmail.com','hotmail.com','yahoo.com','outlook.com','live.com','icloud.com',
        'uol.com.br','bol.com.br','terra.com.br','ig.com.br','oi.com.br',
        'pop.com.br','r7.com','zipmail.com.br','protonmail.com','zoho.com','aol.com',
        'yandex.com','mail.com','msn.com','gmx.com','globomail.com',
    ];

    public function run(array $params)
    {
        $db    = db_connect();
        $cache = \Config\Services::cache();

        CLI::write('🔄 Iniciando recálculo de scores preditivos...', 'yellow');

        // ── 1. Ler configurações de peso ──────────────────────────────────
        $configRows = $db->query("SELECT key, value FROM scoring_config")->getResultArray();
        $cfg = [];
        foreach ($configRows as $row) {
            $cfg[$row['key']] = (float) $row['value'];
        }

        $wCnae       = $cfg['weight_cnae']         ?? 40;
        $wCapital    = $cfg['weight_capital']       ?? 20;
        $wEmail      = $cfg['weight_email']         ?? 15;
        $wNomeF      = $cfg['weight_nome_fantasia'] ?? 10;
        $wLoc        = $cfg['weight_localizacao']   ?? 15;
        $amortFactor = ($cfg['amortization_factor'] ?? 70) / 100.0;
        $capHigh     = $cfg['capital_tier_high']    ?? 100000;
        $capMid      = $cfg['capital_tier_mid']     ?? 20000;

        // ── 2. Ler regras de peso por CNAE ───────────────────────────────
        $cnaeRules = [];
        $ruleRows  = $db->query("SELECT cnae_code, weight FROM cnae_scoring_rules")->getResultArray();
        foreach ($ruleRows as $r) {
            $cnaeRules[$r['cnae_code']] = (int) $r['weight'];
        }
        $maxCnaeWeight = !empty($cnaeRules) ? max(array_values($cnaeRules)) : 1;

        CLI::write('📋 Configurações carregadas: pesos ' . implode('/', [$wCnae, $wCapital, $wEmail, $wNomeF, $wLoc]) . ' | Amortização: ' . ($amortFactor * 100) . '%', 'light_gray');

        // ── 3. Contar total de registros ──────────────────────────────────
        $total = (int) ($db->query("SELECT COUNT(*) AS c FROM carteira_raw")->getRow()->c ?? 0);
        if ($total === 0) {
            CLI::write('⚠️  Nenhum registro encontrado na carteira_raw. Encerrando.', 'red');
            return;
        }

        CLI::write("📊 Total de CNPJs a processar: {$total}", 'light_blue');

        $chunkSize = 2000;
        $offset    = 0;
        $processed = 0;

        $cache->save('scoring_recalculation_progress', 0, 3600);

        // ── 4. Processar em chunks ────────────────────────────────────────
        while ($offset < $total) {
            $rows = $db->query("
                SELECT
                    cr.cnpj,
                    e.cnae_fiscal_principal,
                    e.cnae_fiscal_secundaria,
                    e.nome_fantasia,
                    e.email,
                    emp.capital_social,
                    cl.latitude,
                    cl.longitude
                FROM carteira_raw cr
                LEFT JOIN receita.estabelecimentos e
                       ON (e.cnpj_basico || e.cnpj_ordem || e.cnpj_dv) = cr.cnpj
                LEFT JOIN receita.empresas emp ON emp.cnpj_basico = e.cnpj_basico
                LEFT JOIN client_locations cl ON cl.cnpj = cr.cnpj
                ORDER BY cr.cnpj
                LIMIT {$chunkSize} OFFSET {$offset}
            ")->getResultArray();

            if (empty($rows)) break;

            foreach ($rows as $row) {
                $score     = 0;
                $breakdown = [];

                // ── Bloco 1: CNAE (principal + secundários amortizados) ──
                $principalCode   = trim($row['cnae_fiscal_principal'] ?? '');
                $principalWeight = isset($cnaeRules[$principalCode]) ? $cnaeRules[$principalCode] : 0;
                $principalPts    = $maxCnaeWeight > 0 ? round(($principalWeight / $maxCnaeWeight) * $wCnae) : 0;

                $bestSecWeight = 0;
                if (!empty($row['cnae_fiscal_secundaria'])) {
                    $secCodes = array_map('trim', explode(',', $row['cnae_fiscal_secundaria']));
                    foreach ($secCodes as $sc) {
                        $w = $cnaeRules[$sc] ?? 0;
                        if ($w > $bestSecWeight) $bestSecWeight = $w;
                    }
                }
                $secPts   = $maxCnaeWeight > 0 ? round(($bestSecWeight / $maxCnaeWeight) * $wCnae * $amortFactor) : 0;
                $cnaePts  = max($principalPts, $secPts);
                $score   += $cnaePts;
                $breakdown['cnae'] = $cnaePts;

                // ── Bloco 2: Capital Social ──
                $capital    = (float) ($row['capital_social'] ?? 0);
                $capitalPts = 0;
                if ($capital >= $capHigh)     $capitalPts = $wCapital;
                elseif ($capital >= $capMid)  $capitalPts = (int) round($wCapital * 0.5);
                else                          $capitalPts = (int) round($wCapital * 0.25);
                $score += $capitalPts;
                $breakdown['capital'] = $capitalPts;

                // ── Bloco 3: E-mail Corporativo ──
                $emailPts = 0;
                $emailVal = trim($row['email'] ?? '');
                if (!empty($emailVal) && filter_var($emailVal, FILTER_VALIDATE_EMAIL)) {
                    [, $domain] = explode('@', $emailVal);
                    if (!in_array(strtolower($domain), $this->publicEmailDomains)) {
                        $emailPts = $wEmail;
                    }
                }
                $score += $emailPts;
                $breakdown['email'] = $emailPts;

                // ── Bloco 4: Nome Fantasia ──
                $nomeFPts = !empty(trim($row['nome_fantasia'] ?? '')) ? $wNomeF : 0;
                $score   += $nomeFPts;
                $breakdown['nome_fantasia'] = $nomeFPts;

                // ── Bloco 5: Localização (tem coordenadas = maior chance de CDD próximo) ──
                $locPts = (!empty($row['latitude']) && !empty($row['longitude'])) ? $wLoc : (int) round($wLoc * 0.33);
                $score += $locPts;
                $breakdown['localizacao'] = $locPts;

                // ── Gravar em client_enrichment ──
                $justification = "CNAE:{$cnaePts} + Capital:{$capitalPts} + Email:{$emailPts} + Marca:{$nomeFPts} + Loc:{$locPts}";

                $db->query("
                    INSERT INTO client_enrichment (cnpj, logistics_score, score_breakdown, score_justification, enriched_at, created_at, updated_at)
                    VALUES (?, ?, ?, ?, NOW(), NOW(), NOW())
                    ON CONFLICT (cnpj) DO UPDATE
                    SET logistics_score      = EXCLUDED.logistics_score,
                        score_breakdown      = EXCLUDED.score_breakdown,
                        score_justification  = EXCLUDED.score_justification,
                        enriched_at          = NOW(),
                        updated_at           = NOW()
                ", [
                    $row['cnpj'],
                    min(100, max(0, $score)),
                    json_encode($breakdown),
                    $justification,
                ]);

                $processed++;
            }

            $offset   += $chunkSize;
            $progress  = (int) min(99, round(($processed / $total) * 100));
            $cache->save('scoring_recalculation_progress', $progress, 3600);

            CLI::write("  ✔ {$processed}/{$total} processados ({$progress}%)", 'light_gray');
        }

        $cache->save('scoring_recalculation_progress', 100, 3600);
        CLI::write("✅ Recálculo concluído! {$processed} CNPJs atualizados.", 'green');
    }
}
