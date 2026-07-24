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

$sqlRanking = "
    WITH empresas_score AS (
        SELECT 
            (e.cnpj_basico || e.cnpj_ordem || e.cnpj_dv) AS cnpj,
            emp.razao_social,
            e.cnae_fiscal_principal,
            c.postal_categoria,
            c.postal_score,
            e.data_inicio_atividade,
            TO_DATE(e.data_inicio_atividade, 'YYYYMMDD') AS dt_abertura,
            e.uf,
            e.municipio,
            
            -- 1. Idade da Empresa em Anos
            EXTRACT(YEAR FROM AGE(CURRENT_DATE, TO_DATE(e.data_inicio_atividade, 'YYYYMMDD'))) AS idade_anos,
            
            -- 2. Score Base de Idade (Maior peso para empresas mais novas)
            CASE 
                WHEN TO_DATE(e.data_inicio_atividade, 'YYYYMMDD') >= CURRENT_DATE - INTERVAL '3 years' THEN 100 -- Super Novas (0-3 anos)
                WHEN TO_DATE(e.data_inicio_atividade, 'YYYYMMDD') >= CURRENT_DATE - INTERVAL '5 years' THEN 85  -- Novas (3-5 anos)
                WHEN TO_DATE(e.data_inicio_atividade, 'YYYYMMDD') >= CURRENT_DATE - INTERVAL '10 years' THEN 70 -- Consolidadas (5-10 anos)
                WHEN TO_DATE(e.data_inicio_atividade, 'YYYYMMDD') >= CURRENT_DATE - INTERVAL '15 years' THEN 40 -- Maduras (10-15 anos)
                ELSE 15                                                                                         -- Antigas (>15 anos)
            END AS score_idade,

            -- 3. Fator de Demanda/Sobrevivência por Setor (Tabela SEBRAE/IBGE)
            -- Comércio (30.2% mort.) -> 1.20 | Indústria (27.3%) -> 1.10 | Serviços (26.6%) -> 1.00 | Agro (18%) -> 0.70 | Extrativa (14.3%) -> 0.50
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
        WHERE e.situacao_cadastral = '02' -- Apenas ativas
          AND NOT EXISTS (
              SELECT 1 FROM carteira_raw cr WHERE cr.cnpj = (e.cnpj_basico || e.cnpj_ordem || e.cnpj_dv)
          )
    )
    SELECT 
        cnpj,
        razao_social,
        cnae_fiscal_principal,
        postal_categoria,
        dt_abertura,
        idade_anos,
        postal_score AS score_cnae,
        score_idade,
        fator_setor,
        -- Score Final Combinado: (Score CNAE * 20) * 0.40 + (Score Idade * Fator Setor) * 0.60
        ROUND(
            (COALESCE(postal_score, 0) * 20.0) * 0.40 +
            (score_idade * fator_setor) * 0.60,
            2
        ) AS score_potencial_final
    FROM empresas_score
    ORDER BY score_potencial_final DESC, dt_abertura DESC
    LIMIT 25
";

echo "=== TOP 25 PROSPECTS FORA DE CARTEIRA (RANKING FINAL SIMULADO) ===\n\n";
$rows = $pdo->query($sqlRanking)->fetchAll(PDO::FETCH_ASSOC);

printf("%-15s | %-35s | %-12s | %-10s | %-8s | %-8s | %-6s\n",
    "CNPJ", "Razão Social", "Categoria", "Abertura", "Idade", "CNAE P.", "SCORE FINAL");
echo str_repeat("-", 108) . "\n";

foreach ($rows as $r) {
    printf("%-15s | %-35s | %-12s | %-10s | %6d y | %8d | %11.2f\n",
        $r['cnpj'],
        mb_substr($r['razao_social'], 0, 35),
        $r['postal_categoria'] ?? 'n/a',
        $r['dt_abertura'],
        $r['idade_anos'],
        $r['score_cnae'],
        $r['score_potencial_final']
    );
}

echo "\n=== DISTRIBUIÇÃO DAS EMPRESAS POR FAIXA DE SCORE FINAL ===\n";
$sqlFaixas = "
    WITH empresas_score AS (
        SELECT 
            ROUND(
                (COALESCE(c.postal_score, 0) * 20.0) * 0.40 +
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
                END)) * 0.60,
                2
            ) AS score_final
        FROM receita.estabelecimentos e
        LEFT JOIN cnae_postal_score c ON c.subclasse = e.cnae_fiscal_principal
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
    FROM empresas_score
    GROUP BY faixa_score
    ORDER BY faixa_score
";
$faixas = $pdo->query($sqlFaixas)->fetchAll(PDO::FETCH_ASSOC);
foreach ($faixas as $f) {
    printf("  %-30s: %6d empresas (%5.2f%%)\n", $f['faixa_score'], $f['total'], $f['pct']);
}
