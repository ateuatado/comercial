<?php

$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    foreach (file($envFile) as $line) {
        $line = trim($line);
        if ($line && strpos($line, '#') !== 0 && strpos($line, '=') !== false) {
            [$key, $val] = explode('=', $line, 2);
            putenv(trim($key) . '=' . trim($val));
        }
    }
}

$host   = getenv('database.default.hostname') ?: 'localhost';
$dbname = getenv('database.default.database') ?: 'spiv';
$user   = getenv('database.default.username') ?: 'postgres';
$pass   = getenv('database.default.password') ?: '';

$pdo = new PDO("pgsql:host=$host;dbname=$dbname", $user, $pass);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// 1. Tabela virtual de Medianas de Sobreviventes por Categoria (calculadas dinamicamente)
$sqlSimulation = "
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
            COALESCE(c.postal_score, 0) AS postal_score,
            TO_DATE(e.data_inicio_atividade, 'YYYYMMDD') AS dt_abertura,
            EXTRACT(YEAR FROM AGE(CURRENT_DATE, TO_DATE(e.data_inicio_atividade, 'YYYYMMDD'))) AS idade_anos,
            
            CASE 
                WHEN emp.capital_social IS NULL OR emp.capital_social = '' THEN 0
                ELSE NULLIF(REPLACE(REPLACE(emp.capital_social, '.', ''), ',', '.'), '')::numeric
            END AS capital_social,

            m.mediana_capital_sobrevivente,

            -- Score Idade
            CASE 
                WHEN TO_DATE(e.data_inicio_atividade, 'YYYYMMDD') >= CURRENT_DATE - INTERVAL '3 years' THEN 100
                WHEN TO_DATE(e.data_inicio_atividade, 'YYYYMMDD') >= CURRENT_DATE - INTERVAL '5 years' THEN 85
                WHEN TO_DATE(e.data_inicio_atividade, 'YYYYMMDD') >= CURRENT_DATE - INTERVAL '10 years' THEN 70
                WHEN TO_DATE(e.data_inicio_atividade, 'YYYYMMDD') >= CURRENT_DATE - INTERVAL '15 years' THEN 40
                ELSE 15
            END AS score_idade,

            -- Fator Mortalidade Setorial
            CASE 
                WHEN c.postal_categoria IN ('ecommerce', 'varejo') THEN 1.20
                WHEN c.postal_categoria IN ('distribuicao', 'industria') THEN 1.10
                WHEN c.postal_categoria IN ('servico', 'saude', 'educacao') THEN 1.00
                WHEN c.postal_categoria = 'agro' THEN 0.70
                ELSE 0.50
            END AS fator_setor

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
            -- Razão de Capital frente à Mediana do Setor
            CASE 
                WHEN mediana_capital_sobrevivente IS NULL OR mediana_capital_sobrevivente = 0 THEN 1.0
                ELSE (capital_social / mediana_capital_sobrevivente)
            END AS razao_capital,

            -- Score Adequação de Capital (0-100)
            CASE 
                WHEN capital_social = 0 THEN 20
                WHEN (capital_social / NULLIF(mediana_capital_sobrevivente, 0)) >= 2.0 THEN 100
                WHEN (capital_social / NULLIF(mediana_capital_sobrevivente, 0)) >= 1.0 THEN 85
                WHEN (capital_social / NULLIF(mediana_capital_sobrevivente, 0)) >= 0.5 THEN 60
                WHEN (capital_social / NULLIF(mediana_capital_sobrevivente, 0)) >= 0.2 THEN 35
                ELSE 20
            END AS score_capital

        FROM prospects_base
    )
    SELECT 
        cnpj,
        razao_social,
        categoria,
        dt_abertura,
        idade_anos,
        capital_social,
        mediana_capital_sobrevivente,
        ROUND(razao_capital::numeric, 2) AS ratio_capital,
        postal_score AS score_cnae,
        score_idade,
        score_capital,
        
        -- COMPOSIÇÃO DO SCORE FINAL:
        -- 35% CNAE Postal + 35% Tempo de Vida (Idade * Fator Setor) + 30% Adequação de Capital
        ROUND(
            (postal_score * 20.0) * 0.35 +
            (score_idade * fator_setor) * 0.35 +
            (score_capital) * 0.30,
            2
        ) AS score_final

    FROM prospects_scored
    ORDER BY score_final DESC, dt_abertura DESC
    LIMIT 20
";

echo "=== TOP 20 PROSPECTS COM METRICA DE CAPITAL SOCIAL RELATIVO A MEDIANA ===\n\n";
$rows = $pdo->query($sqlSimulation)->fetchAll(PDO::FETCH_ASSOC);

printf("%-15s | %-30s | %-11s | %-12s | %-12s | %-6s | %-6s | %-6s | %-6s\n",
    "CNPJ", "Razão Social", "Categoria", "Cap. Social", "Mediana Setor", "SC_CNAE", "SC_IDD", "SC_CAP", "FINAL");
echo str_repeat("-", 125) . "\n";

foreach ($rows as $r) {
    printf("%-15s | %-30s | %-11s | R$%10s | R$%10s | %7d | %6d | %6d | %6.2f\n",
        $r['cnpj'],
        mb_substr($r['razao_social'], 0, 30),
        $r['categoria'],
        number_format((float)$r['capital_social'], 0, ',', '.'),
        number_format((float)$r['mediana_capital_sobrevivente'], 0, ',', '.'),
        $r['score_cnae'],
        $r['score_idade'],
        $r['score_capital'],
        $r['score_final']
    );
}

echo "\n=== DISTRIBUIÇÃO DAS EMPRESAS POR FAIXA DE SCORE FINAL COM CAPITAL ===\n";
$sqlFaixas = "
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
    prospects_scored AS (
        SELECT 
            ROUND(
                (COALESCE(c.postal_score, 0) * 20.0) * 0.35 +
                ((CASE 
                    WHEN TO_DATE(e.data_inicio_atividade, 'YYYYMMDD') >= CURRENT_DATE - INTERVAL '3 years' THEN 100
                    WHEN TO_DATE(e.data_inicio_atividade, 'YYYYMMDD') >= CURRENT_DATE - INTERVAL '5 years' THEN 85
                    WHEN TO_DATE(e.data_inicio_atividade, 'YYYYMMDD') >= CURRENT_DATE - INTERVAL '10 years' THEN 70
                    WHEN TO_DATE(e.data_inicio_atividade, 'YYYYMMDD') >= CURRENT_DATE - INTERVAL '15 years' THEN 40
                    ELSE 15
                END) * 
                (CASE 
                    WHEN c.postal_categoria IN ('ecommerce', 'varejo') THEN 1.20
                    WHEN c.postal_categoria IN ('distribuicao', 'industria') THEN 1.10
                    WHEN c.postal_categoria IN ('servico', 'saude', 'educacao') THEN 1.00
                    WHEN c.postal_categoria = 'agro' THEN 0.70
                    ELSE 0.50
                END)) * 0.35 +
                ((CASE 
                    WHEN (NULLIF(REPLACE(REPLACE(emp.capital_social, '.', ''), ',', '.'), '')::numeric / NULLIF(m.mediana_capital_sobrevivente, 0)) >= 2.0 THEN 100
                    WHEN (NULLIF(REPLACE(REPLACE(emp.capital_social, '.', ''), ',', '.'), '')::numeric / NULLIF(m.mediana_capital_sobrevivente, 0)) >= 1.0 THEN 85
                    WHEN (NULLIF(REPLACE(REPLACE(emp.capital_social, '.', ''), ',', '.'), '')::numeric / NULLIF(m.mediana_capital_sobrevivente, 0)) >= 0.5 THEN 60
                    WHEN (NULLIF(REPLACE(REPLACE(emp.capital_social, '.', ''), ',', '.'), '')::numeric / NULLIF(m.mediana_capital_sobrevivente, 0)) >= 0.2 THEN 35
                    ELSE 20
                END)) * 0.30,
                2
            ) AS score_final
        FROM receita.estabelecimentos e
        JOIN receita.empresas emp ON emp.cnpj_basico = e.cnpj_basico
        LEFT JOIN cnae_postal_score c ON c.subclasse = e.cnae_fiscal_principal
        LEFT JOIN medianas_setor m ON m.categoria = COALESCE(c.postal_categoria, 'servico')
        WHERE e.situacao_cadastral = '02'
          AND NOT EXISTS (
              SELECT 1 FROM carteira_raw cr WHERE cr.cnpj = (e.cnpj_basico || e.cnpj_ordem || e.cnpj_dv)
          )
    )
    SELECT 
        CASE 
            WHEN score_final >= 80 THEN '1. Excelente (>= 80 pts)'
            WHEN score_final >= 60 THEN '2. Alto (60 - 79 pts)'
            WHEN score_final >= 40 THEN '3. Médio (40 - 59 pts)'
            WHEN score_final >= 20 THEN '4. Baixo (20 - 39 pts)'
            ELSE '5. Muito Baixo (< 20 pts)'
        END AS faixa_score,
        COUNT(*) AS total,
        ROUND(COUNT(*) * 100.0 / SUM(COUNT(*)) OVER(), 2) AS pct
    FROM prospects_scored
    GROUP BY faixa_score
    ORDER BY faixa_score
";

$faixas = $pdo->query($sqlFaixas)->fetchAll(PDO::FETCH_ASSOC);
foreach ($faixas as $f) {
    printf("  %-30s: %6d empresas (%5.2f%%)\n", $f['faixa_score'], $f['total'], $f['pct']);
}
