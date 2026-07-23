<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class DiagCnpj extends BaseCommand
{
    protected $group       = 'Diagnostic';
    protected $name        = 'diag:cnpj';
    protected $description = 'Testa cálculo completo de score no Ranking de Potencial';
    protected $usage       = 'diag:cnpj';

    public function run(array $params)
    {
        $db = \Config\Database::connect();

        // 1. Ler regras de CNAE
        $cnaeRules = [];
        $ruleRows  = $db->query("SELECT cnae_code, weight FROM cnae_scoring_rules")->getResultArray();
        foreach ($ruleRows as $r) {
            $cnaeRules[$r['cnae_code']] = (int) $r['weight'];
        }
        $maxCnaeWeight = !empty($cnaeRules) ? max(array_values($cnaeRules)) : 1;

        $publicEmailDomains = [
            'gmail.com','hotmail.com','yahoo.com','outlook.com','live.com','icloud.com',
            'uol.com.br','bol.com.br','terra.com.br','ig.com.br','oi.com.br',
            'pop.com.br','r7.com','zipmail.com.br','protonmail.com','zoho.com','aol.com',
            'yandex.com','mail.com','msn.com','gmx.com','globomail.com',
        ];

        // 2. Busca leads livres ordenados por potencial
        $rows = $db->query("
            SELECT
                (e.cnpj_basico || e.cnpj_ordem || e.cnpj_dv) AS cnpj,
                COALESCE(e.nome_fantasia, '') AS nome_fantasia,
                COALESCE(emp.razao_social, '') AS razao_social,
                e.cnae_fiscal_principal,
                e.cnae_fiscal_secundaria,
                COALESCE(e.email, '') AS email,
                emp.capital_social,
                m.descricao AS municipio_nome,
                e.uf,
                COALESCE(loc.latitude, 0) AS loc_lat,
                COALESCE(loc.longitude, 0) AS loc_lng,
                cw.rfb_situacao_cadastral
            FROM receita.estabelecimentos e
            LEFT JOIN receita.empresas emp ON emp.cnpj_basico = e.cnpj_basico
            LEFT JOIN receita.municipios m ON e.municipio = m.codigo
            LEFT JOIN client_locations loc ON loc.cnpj = (e.cnpj_basico || e.cnpj_ordem || e.cnpj_dv)
            LEFT JOIN client_wallets cw ON cw.cnpj = (e.cnpj_basico || e.cnpj_ordem || e.cnpj_dv)
            LEFT JOIN carteira_raw cr ON REGEXP_REPLACE(cr.cnpj, '[^0-9]', '', 'g') = (e.cnpj_basico || e.cnpj_ordem || e.cnpj_dv)
            WHERE cr.cnpj IS NULL
              AND (e.situacao_cadastral IS NULL OR e.situacao_cadastral = '02')
            ORDER BY
                CASE WHEN CAST(REPLACE(REPLACE(COALESCE(emp.capital_social, '0'), '.', ''), ',', '.') AS NUMERIC) >= 100000 THEN 30
                     WHEN CAST(REPLACE(REPLACE(COALESCE(emp.capital_social, '0'), '.', ''), ',', '.') AS NUMERIC) >= 20000 THEN 20
                     ELSE 10 END +
                CASE WHEN e.email IS NOT NULL AND e.email != '' AND e.email NOT LIKE '%@gmail.com' AND e.email NOT LIKE '%@hotmail.com' THEN 15 ELSE 0 END +
                CASE WHEN e.nome_fantasia IS NOT NULL AND e.nome_fantasia != '' THEN 10 ELSE 0 END +
                CASE WHEN loc.latitude IS NOT NULL THEN 15 ELSE 5 END DESC,
                CAST(REPLACE(REPLACE(COALESCE(emp.capital_social, '0'), '.', ''), ',', '.') AS NUMERIC) DESC
            LIMIT 5
        ")->getResultArray();

        foreach ($rows as &$row) {
            $score     = 0;
            $breakdown = [];

            // CNAE
            $principalCode   = trim($row['cnae_fiscal_principal'] ?? '');
            $principalWeight = $cnaeRules[$principalCode] ?? 0;
            $principalPts    = $maxCnaeWeight > 0 ? round(($principalWeight / $maxCnaeWeight) * 40) : 0;

            $bestSecWeight = 0;
            if (!empty($row['cnae_fiscal_secundaria'])) {
                $secCodes = array_map('trim', explode(',', $row['cnae_fiscal_secundaria']));
                foreach ($secCodes as $sc) {
                    $w = $cnaeRules[$sc] ?? 0;
                    if ($w > $bestSecWeight) $bestSecWeight = $w;
                }
            }
            $secPts   = $maxCnaeWeight > 0 ? round(($bestSecWeight / $maxCnaeWeight) * 40 * 0.7) : 0;
            $cnaePts  = max($principalPts, $secPts);
            if ($cnaePts == 0) $cnaePts = 15; // fallback base para estabelecimentos ativos
            $score   += $cnaePts;
            $breakdown['cnae'] = $cnaePts;

            // Capital Social
            $capClean = (float) str_replace(['.', ','], ['', '.'], $row['capital_social'] ?? '0');
            $capitalPts = 5;
            if ($capClean >= 100000)     $capitalPts = 20;
            elseif ($capClean >= 20000)  $capitalPts = 10;
            $score += $capitalPts;
            $breakdown['capital'] = $capitalPts;

            // Email
            $emailPts = 0;
            $emailVal = trim($row['email'] ?? '');
            if (!empty($emailVal) && filter_var($emailVal, FILTER_VALIDATE_EMAIL)) {
                [, $domain] = explode('@', $emailVal);
                if (!in_array(strtolower($domain), $publicEmailDomains)) {
                    $emailPts = 15;
                }
            }
            $score += $emailPts;
            $breakdown['email'] = $emailPts;

            // Nome Fantasia
            $nomeFPts = !empty(trim($row['nome_fantasia'] ?? '')) ? 10 : 0;
            $score   += $nomeFPts;
            $breakdown['nome_fantasia'] = $nomeFPts;

            // Localizacao
            $locPts = (!empty($row['loc_lat']) && !empty($row['loc_lng'])) ? 15 : 5;
            $score += $locPts;
            $breakdown['localizacao'] = $locPts;

            $row['logistics_score'] = min(100, max(0, $score));
            $row['score_breakdown'] = $breakdown;
        }

        CLI::write("=== LEADS LIVRES PROCESSADOS E RANKEADOS ===", 'green');
        print_r($rows);
    }
}
