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

echo "=== 1. Mediana do Capital Social de Empresas Sobreviventes (> 5 Anos) por Categoria CNAE ===\n\n";

$sqlMedianaCat = "
    WITH empresas_clean AS (
        SELECT 
            COALESCE(c.postal_categoria, 'servico') AS categoria,
            CASE 
                WHEN emp.capital_social IS NULL OR emp.capital_social = '' THEN 0
                ELSE NULLIF(REPLACE(REPLACE(emp.capital_social, '.', ''), ',', '.'), '')::numeric
            END AS capital
        FROM receita.estabelecimentos e
        JOIN receita.empresas emp ON emp.cnpj_basico = e.cnpj_basico
        LEFT JOIN cnae_postal_score c ON c.subclasse = e.cnae_fiscal_principal
        WHERE e.situacao_cadastral = '02'
          AND TO_DATE(e.data_inicio_atividade, 'YYYYMMDD') < CURRENT_DATE - INTERVAL '5 years'
    )
    SELECT 
        categoria,
        COUNT(*) AS total_sobreviventes,
        ROUND(PERCENTILE_CONT(0.5) WITHIN GROUP (ORDER BY capital)::numeric, 2) AS mediana_capital,
        ROUND(AVG(capital)::numeric, 2) AS media_capital
    FROM empresas_clean
    WHERE capital > 0
    GROUP BY categoria
    ORDER BY mediana_capital DESC
";

$rowsCat = $pdo->query($sqlMedianaCat)->fetchAll(PDO::FETCH_ASSOC);

printf("%-15s | %-12s | %-18s | %-18s\n", "Categoria", "Qtd (>5 anos)", "Mediana Capital (R$)", "Média Capital (R$)");
echo str_repeat("-", 72) . "\n";
foreach ($rowsCat as $r) {
    printf("%-15s | %12d | R$ %15s | R$ %15s\n",
        $r['categoria'],
        $r['total_sobreviventes'],
        number_format((float)$r['mediana_capital'], 2, ',', '.'),
        number_format((float)$r['media_capital'], 2, ',', '.')
    );
}

echo "\n=== 2. Mediana por Divisão CNAE (Top 15 Divisões mais frequentes) ===\n\n";

$sqlMedianaDiv = "
    WITH empresas_clean AS (
        SELECT 
            c.divisao,
            c.secao,
            CASE 
                WHEN emp.capital_social IS NULL OR emp.capital_social = '' THEN 0
                ELSE NULLIF(REPLACE(REPLACE(emp.capital_social, '.', ''), ',', '.'), '')::numeric
            END AS capital
        FROM receita.estabelecimentos e
        JOIN receita.empresas emp ON emp.cnpj_basico = e.cnpj_basico
        JOIN cnae_postal_score c ON c.subclasse = e.cnae_fiscal_principal
        WHERE e.situacao_cadastral = '02'
          AND TO_DATE(e.data_inicio_atividade, 'YYYYMMDD') < CURRENT_DATE - INTERVAL '5 years'
    )
    SELECT 
        divisao,
        COUNT(*) AS total_sobreviventes,
        ROUND(PERCENTILE_CONT(0.5) WITHIN GROUP (ORDER BY capital)::numeric, 2) AS mediana_capital,
        MIN(secao) AS secao_nome
    FROM empresas_clean
    WHERE capital > 0
    GROUP BY divisao
    HAVING COUNT(*) >= 50
    ORDER BY total_sobreviventes DESC
    LIMIT 15
";

$rowsDiv = $pdo->query($sqlMedianaDiv)->fetchAll(PDO::FETCH_ASSOC);

printf("%-10s | %-12s | %-18s | %-30s\n", "Divisão", "Qtd (>5 anos)", "Mediana Capital (R$)", "Seção");
echo str_repeat("-", 78) . "\n";
foreach ($rowsDiv as $r) {
    printf("%-10s | %12d | R$ %15s | %-30s\n",
        $r['divisao'],
        $r['total_sobreviventes'],
        number_format((float)$r['mediana_capital'], 2, ',', '.'),
        mb_substr($r['secao_nome'] ?? '', 0, 30)
    );
}
