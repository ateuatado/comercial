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

$sqlSimulacao4Pilares = "
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
            e.email,
            LOWER(TRIM(SUBSTRING(e.email FROM '@(.*)$'))) AS dominio_email,
            
            CASE 
                WHEN emp.capital_social IS NULL OR emp.capital_social = '' THEN 0
                ELSE NULLIF(REPLACE(REPLACE(emp.capital_social, '.', ''), ',', '.'), '')::numeric
            END AS capital_social,

            COALESCE(m.mediana_capital_sobrevivente, 5000.00) AS mediana_setor,

            -- Pilar 2: Score Idade
            CASE 
                WHEN TO_DATE(e.data_inicio_atividade, 'YYYYMMDD') >= CURRENT_DATE - INTERVAL '3 years' THEN 100
                WHEN TO_DATE(e.data_inicio_atividade, 'YYYYMMDD') >= CURRENT_DATE - INTERVAL '5 years' THEN 85
                WHEN TO_DATE(e.data_inicio_atividade, 'YYYYMMDD') >= CURRENT_DATE - INTERVAL '10 years' THEN 70
                WHEN TO_DATE(e.data_inicio_atividade, 'YYYYMMDD') >= CURRENT_DATE - INTERVAL '15 years' THEN 40
                ELSE 15
            END AS score_idade,

            -- Pilar 2: Fator Mortalidade Setorial
            CASE 
                WHEN c.postal_categoria IN ('ecommerce', 'varejo') THEN 1.20
                WHEN c.postal_categoria IN ('distribuicao', 'industria') THEN 1.10
                WHEN c.postal_categoria IN ('servico', 'saude', 'educacao') THEN 1.00
                WHEN c.postal_categoria = 'agro' THEN 0.70
                ELSE 0.50
            END AS fator_setor,

            -- Pilar 4: Score Maturidade Digital (E-mail)
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

            -- Pilar 3: Score Adequação de Capital (0-100)
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
        categoria,
        email,
        score_cnae,
        score_idade,
        score_capital,
        score_email,
        -- FÓRMULA FINAL (4 PILARES):
        -- 30% CNAE Postal + 30% Idade/Mortalidade + 25% Adequação Capital + 15% Maturidade Digital (Email)
        ROUND(
            (score_cnae * 0.30) +
            (score_idade * fator_setor * 0.30) +
            (score_capital * 0.25) +
            (score_email * 0.15),
            2
        ) AS score_final
    FROM prospects_scored
    ORDER BY score_final DESC, dt_abertura DESC
    LIMIT 20
";

echo "=== TOP 20 PROSPECTS (FÓRMULA 4 PILARES: CNAE + IDADE + CAPITAL + EMAIL) ===\n\n";
$rows = $pdo->query($sqlSimulacao4Pilares)->fetchAll(PDO::FETCH_ASSOC);

printf("%-15s | %-30s | %-10s | %-30s | %-4s | %-4s | %-4s | %-4s | %-6s\n",
    "CNPJ", "Razão Social", "Categoria", "E-mail", "CNAE", "IDD", "CAP", "EML", "FINAL");
echo str_repeat("-", 125) . "\n";

foreach ($rows as $r) {
    printf("%-15s | %-30s | %-10s | %-30s | %4d | %4d | %4d | %4d | %6.2f\n",
        $r['cnpj'],
        mb_substr($r['razao_social'], 0, 30),
        $r['categoria'],
        mb_substr($r['email'] ?? 'SEM EMAIL', 0, 30),
        $r['score_cnae'],
        $r['score_idade'],
        $r['score_capital'],
        $r['score_email'],
        $r['score_final']
    );
}

echo "\n=== DISTRIBUIÇÃO DAS EMPRESAS POR FAIXA DE SCORE FINAL (4 PILARES) ===\n";
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
    prospects_base AS (
        SELECT 
            (COALESCE(c.postal_score, 0) * 20) AS score_cnae,
            CASE 
                WHEN TO_DATE(e.data_inicio_atividade, 'YYYYMMDD') >= CURRENT_DATE - INTERVAL '3 years' THEN 100
                WHEN TO_DATE(e.data_inicio_atividade, 'YYYYMMDD') >= CURRENT_DATE - INTERVAL '5 years' THEN 85
                WHEN TO_DATE(e.data_inicio_atividade, 'YYYYMMDD') >= CURRENT_DATE - INTERVAL '10 years' THEN 70
                WHEN TO_DATE(e.data_inicio_atividade, 'YYYYMMDD') >= CURRENT_DATE - INTERVAL '15 years' THEN 40
                ELSE 15
            END AS score_idade,
            CASE 
                WHEN c.postal_categoria IN ('ecommerce', 'varejo') THEN 1.20
                WHEN c.postal_categoria IN ('distribuicao', 'industria') THEN 1.10
                WHEN c.postal_categoria IN ('servico', 'saude', 'educacao') THEN 1.00
                WHEN c.postal_categoria = 'agro' THEN 0.70
                ELSE 0.50
            END AS fator_setor,
            CASE 
                WHEN (CASE WHEN emp.capital_social IS NULL OR emp.capital_social = '' THEN 0 ELSE NULLIF(REPLACE(REPLACE(emp.capital_social, '.', ''), ',', '.'), '')::numeric END / NULLIF(m.mediana_capital_sobrevivente, 0)) >= 2.0 THEN 100
                WHEN (CASE WHEN emp.capital_social IS NULL OR emp.capital_social = '' THEN 0 ELSE NULLIF(REPLACE(REPLACE(emp.capital_social, '.', ''), ',', '.'), '')::numeric END / NULLIF(m.mediana_capital_sobrevivente, 0)) >= 1.0 THEN 85
                WHEN (CASE WHEN emp.capital_social IS NULL OR emp.capital_social = '' THEN 0 ELSE NULLIF(REPLACE(REPLACE(emp.capital_social, '.', ''), ',', '.'), '')::numeric END / NULLIF(m.mediana_capital_sobrevivente, 0)) >= 0.5 THEN 60
                WHEN (CASE WHEN emp.capital_social IS NULL OR emp.capital_social = '' THEN 0 ELSE NULLIF(REPLACE(REPLACE(emp.capital_social, '.', ''), ',', '.'), '')::numeric END / NULLIF(m.mediana_capital_sobrevivente, 0)) >= 0.2 THEN 35
                ELSE 20
            END AS score_capital,
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
    )
    SELECT 
        CASE 
            WHEN (score_cnae * 0.30 + score_idade * fator_setor * 0.30 + score_capital * 0.25 + score_email * 0.15) >= 80 THEN '1. Excelente (>= 80 pts)'
            WHEN (score_cnae * 0.30 + score_idade * fator_setor * 0.30 + score_capital * 0.25 + score_email * 0.15) >= 60 THEN '2. Alto (60 - 79 pts)'
            WHEN (score_cnae * 0.30 + score_idade * fator_setor * 0.30 + score_capital * 0.25 + score_email * 0.15) >= 40 THEN '3. Médio (40 - 59 pts)'
            WHEN (score_cnae * 0.30 + score_idade * fator_setor * 0.30 + score_capital * 0.25 + score_email * 0.15) >= 20 THEN '4. Baixo (20 - 39 pts)'
            ELSE '5. Muito Baixo (< 20 pts)'
        END AS faixa,
        COUNT(*) AS total,
        ROUND(COUNT(*) * 100.0 / SUM(COUNT(*)) OVER(), 2) AS pct
    FROM prospects_base
    GROUP BY faixa
    ORDER BY faixa
";

$faixas = $pdo->query($sqlFaixas)->fetchAll(PDO::FETCH_ASSOC);
foreach ($faixas as $f) {
    printf("  %-30s: %6d empresas (%5.2f%%)\n", $f['faixa'], $f['total'], $f['pct']);
}
