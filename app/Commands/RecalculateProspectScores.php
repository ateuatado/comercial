<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Command: prospects:recalculate
 * 
 * Recalcula o ranking de potencial de prospecção para todas as empresas ativas
 * fora da carteira de clientes (`carteira_raw`) e grava na tabela de cache `prospect_scores`.
 * 
 * Lógica de 4 Pilares:
 *  - 30% Score CNAE Postal (tabela cnae_postal_score)
 *  - 30% Score Idade * Fator Setor (Taxa de Mortalidade SEBRAE/IBGE)
 *  - 25% Adequação de Capital Social (Razão vs Mediana dos Sobreviventes do Setor)
 *  - 15% Maturidade Digital (Domínio de E-mail Corporativo Próprio)
 * 
 * Uso: php spark prospects:recalculate
 */
class RecalculateProspectScores extends BaseCommand
{
    protected $group       = 'SPIV';
    protected $name        = 'prospects:recalculate';
    protected $description = 'Recalcula e atualiza o ranking em cache de prospects fora de carteira.';

    public function run(array $params)
    {
        $db = db_connect();
        CLI::write('🚀 Iniciando recálculo do Ranking de Prospects (Fórmula 4 Pilares)...', 'yellow');

        $startTime = microtime(true);

        // 1. Limpar / Reiniciar tabela de cache
        $db->query("TRUNCATE TABLE prospect_scores");

        // 2. Executar inserção em lote com cálculo da mediana setorial via CTE
        $sql = "
            INSERT INTO prospect_scores (
                cnpj, razao_social, cnae_fiscal_principal, postal_categoria,
                score_cnae, score_idade, score_capital, score_email, fator_setor, score_final,
                dt_abertura, idade_anos, capital_social, mediana_setor, razao_capital,
                uf, municipio, calculated_at
            )
            WITH medianas_setor AS (
                SELECT 
                    COALESCE(c.postal_categoria, 'servico') AS categoria,
                    PERCENTILE_CONT(0.5) WITHIN GROUP (
                        ORDER BY CASE 
                            WHEN emp.capital_social IS NULL OR emp.capital_social = '' THEN 0
                            ELSE NULLIF(REPLACE(REPLACE(emp.capital_social, '.', ''), ',', '.'), '')::numeric
                        END
                    )::numeric AS mediana_capital_sobrevivente
                FROM receita.estabelecimentos e
                JOIN receita.empresas emp ON emp.cnpj_basico = e.cnpj_basico
                LEFT JOIN cnae_postal_score c ON c.subclasse = e.cnae_fiscal_principal
                WHERE e.situacao_cadastral = '02'
                  AND TO_DATE(e.data_inicio_atividade, 'YYYYMMDD') < CURRENT_DATE - INTERVAL '5 years'
                  AND NULLIF(REPLACE(REPLACE(emp.capital_social, '.', ''), ',', '.'), '')::numeric > 0
                GROUP BY COALESCE(c.postal_categoria, 'servico')
            ),
            prospects_base AS (
                SELECT 
                    (e.cnpj_basico || e.cnpj_ordem || e.cnpj_dv) AS cnpj,
                    emp.razao_social,
                    e.cnae_fiscal_principal,
                    COALESCE(c.postal_categoria, 'servico') AS categoria,
                    COALESCE(c.postal_score, 0) AS postal_score_raw,
                    TO_DATE(e.data_inicio_atividade, 'YYYYMMDD') AS dt_abertura,
                    EXTRACT(YEAR FROM AGE(CURRENT_DATE, TO_DATE(e.data_inicio_atividade, 'YYYYMMDD')))::smallint AS idade_anos,
                    e.uf,
                    e.municipio,
                    e.email,
                    
                    CASE 
                        WHEN emp.capital_social IS NULL OR emp.capital_social = '' THEN 0
                        ELSE NULLIF(REPLACE(REPLACE(emp.capital_social, '.', ''), ',', '.'), '')::numeric
                    END AS capital_social,

                    COALESCE(m.mediana_capital_sobrevivente, 5000.00) AS mediana_setor,

                    -- Score Idade (Pilar 2)
                    CASE 
                        WHEN TO_DATE(e.data_inicio_atividade, 'YYYYMMDD') >= CURRENT_DATE - INTERVAL '3 years' THEN 100
                        WHEN TO_DATE(e.data_inicio_atividade, 'YYYYMMDD') >= CURRENT_DATE - INTERVAL '5 years' THEN 85
                        WHEN TO_DATE(e.data_inicio_atividade, 'YYYYMMDD') >= CURRENT_DATE - INTERVAL '10 years' THEN 70
                        WHEN TO_DATE(e.data_inicio_atividade, 'YYYYMMDD') >= CURRENT_DATE - INTERVAL '15 years' THEN 40
                        ELSE 15
                    END AS score_idade,

                    -- Fator Mortalidade Setorial (Pilar 2)
                    CASE 
                        WHEN c.postal_categoria IN ('ecommerce', 'varejo') THEN 1.20
                        WHEN c.postal_categoria IN ('distribuicao', 'industria') THEN 1.10
                        WHEN c.postal_categoria IN ('servico', 'saude', 'educacao') THEN 1.00
                        WHEN c.postal_categoria = 'agro' THEN 0.70
                        ELSE 0.50
                    END AS fator_setor,

                    -- Score Maturidade Digital (Pilar 4)
                    CASE 
                        WHEN e.email IS NULL OR TRIM(e.email) = '' OR e.email NOT LIKE '%@%' THEN 0
                        WHEN LOWER(TRIM(SUBSTRING(e.email FROM '@(.*)$'))) IN (
                            'gmail.com', 'gmail.com.br', 'hotmail.com', 'hotmail.com.br', 'outlook.com', 'outlook.com.br',
                            'yahoo.com', 'yahoo.com.br', 'ymail.com', 'bol.com.br', 'uol.com.br', 'ig.com.br',
                            'terra.com.br', 'globo.com', 'globomail.com', 'icloud.com', 'me.com', 'msn.com', 'live.com', 'oi.com.br'
                        ) THEN 40
                        ELSE 100
                    END AS score_email

                FROM receita.estabelecimentos e
                JOIN receita.empresas emp ON emp.cnpj_basico = e.cnpj_basico
                LEFT JOIN cnae_postal_score c ON c.subclasse = e.cnae_fiscal_principal
                LEFT JOIN medianas_setor m ON m.categoria = COALESCE(c.postal_categoria, 'servico')
                WHERE e.situacao_cadastral = '02'
                  AND NOT EXISTS (
                      SELECT 1 FROM carteira_raw cr WHERE cr.cnpj = (e.cnpj_basico || e.cnpj_ordem || e.cnpj_dv)
                  )
            ),
            prospects_scored AS (
                SELECT 
                    *,
                    (postal_score_raw * 20)::smallint AS score_cnae,
                    ROUND((capital_social / NULLIF(mediana_setor, 0))::numeric, 2) AS razao_capital,

                    -- Score Adequação de Capital (Pilar 3)
                    (CASE 
                        WHEN capital_social = 0 THEN 20
                        WHEN (capital_social / NULLIF(mediana_setor, 0)) >= 2.0 THEN 100
                        WHEN (capital_social / NULLIF(mediana_setor, 0)) >= 1.0 THEN 85
                        WHEN (capital_social / NULLIF(mediana_setor, 0)) >= 0.5 THEN 60
                        WHEN (capital_social / NULLIF(mediana_setor, 0)) >= 0.2 THEN 35
                        ELSE 20
                    END)::smallint AS score_capital

                FROM prospects_base
            )
            SELECT 
                cnpj,
                razao_social,
                cnae_fiscal_principal,
                categoria,
                score_cnae,
                score_idade,
                score_capital,
                score_email,
                fator_setor,
                ROUND(
                    (score_cnae * 0.30) +
                    (score_idade * fator_setor * 0.30) +
                    (score_capital * 0.25) +
                    (score_email * 0.15),
                    2
                ) AS score_final,
                dt_abertura,
                idade_anos,
                capital_social,
                mediana_setor,
                razao_capital,
                uf,
                municipio,
                NOW()
            FROM prospects_scored
        ";

        try {
            $db->query($sql);
        } catch (\Throwable $e) {
            CLI::error("❌ ERRO SQL: " . $e->getMessage());
            return;
        }

        $elapsed = round(microtime(true) - $startTime, 2);
        $totalInserted = (int) ($db->query("SELECT COUNT(*) AS c FROM prospect_scores")->getRow()->c ?? 0);

        CLI::write("✅ Concluído em {$elapsed}s! Total de prospects processados: {$totalInserted}", 'green');

        // Distribuição estatística
        $dist = $db->query("
            SELECT 
                CASE 
                    WHEN score_final >= 80 THEN '1. Excelente (>= 80 pts)'
                    WHEN score_final >= 60 THEN '2. Alto (60 - 79 pts)'
                    WHEN score_final >= 40 THEN '3. Médio (40 - 59 pts)'
                    WHEN score_final >= 20 THEN '4. Baixo (20 - 39 pts)'
                    ELSE '5. Muito Baixo (< 20 pts)'
                END AS faixa,
                COUNT(*) AS total
            FROM prospect_scores
            GROUP BY faixa ORDER BY faixa
        ")->getResultArray();

        CLI::write("\n=== Distribuição de Scores Persistidos (4 Pilares) ===", 'cyan');
        foreach ($dist as $d) {
            $pct = round($d['total'] * 100.0 / $totalInserted, 2);
            CLI::write(sprintf("  %-30s: %6d (%5.2f%%)", $d['faixa'], $d['total'], $pct));
        }
    }
}
