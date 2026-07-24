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

echo "=== Total de estabelecimentos na base ===\n";
$total = $pdo->query("SELECT COUNT(*) FROM receita.estabelecimentos")->fetchColumn();
$ativas = $pdo->query("SELECT COUNT(*) FROM receita.estabelecimentos WHERE situacao_cadastral = '02'")->fetchColumn();
echo "Total: {$total} | Ativas (situacao_cadastral='02'): {$ativas}\n\n";

echo "=== Distribuição de Estabelecimentos ATIVOS por Faixa de Abertura (Idade) ===\n";
$sqlIdade = "
    SELECT 
        CASE 
            WHEN data_inicio_atividade >= '2024-01-01' THEN '1. Super Nova (2024-2026 / 0-2 anos)'
            WHEN data_inicio_atividade >= '2021-01-01' THEN '2. Nova (2021-2023 / 3-5 anos)'
            WHEN data_inicio_atividade >= '2016-01-01' THEN '3. Consolidada (2016-2020 / 6-10 anos)'
            WHEN data_inicio_atividade >= '2010-01-01' THEN '4. Madura (2010-2015 / 11-16 anos)'
            ELSE '5. Antiga (Antes de 2010 / >16 anos)'
        END AS faixa_idade,
        COUNT(*) AS total_ativas,
        ROUND(COUNT(*) * 100.0 / {$ativas}, 2) AS pct
    FROM receita.estabelecimentos
    WHERE situacao_cadastral = '02'
    GROUP BY faixa_idade
    ORDER BY faixa_idade
";
$rowsIdade = $pdo->query($sqlIdade)->fetchAll(PDO::FETCH_ASSOC);
foreach ($rowsIdade as $r) {
    printf("  %-42s: %6d (%5.2f%%)\n", $r['faixa_idade'], $r['total_ativas'], $r['pct']);
}

echo "\n=== Cruzamento: Setor (via cnae_postal_score) x Faixa de Idade ===\n";
$sqlCruzado = "
    SELECT 
        COALESCE(c.postal_categoria, 'sem_cnae') AS categoria,
        COUNT(*) FILTER (WHERE e.data_inicio_atividade >= '2021-01-01') AS ate_5_anos,
        COUNT(*) FILTER (WHERE e.data_inicio_atividade >= '2016-01-01' AND e.data_inicio_atividade < '2021-01-01') AS de_6_a_10_anos,
        COUNT(*) FILTER (WHERE e.data_inicio_atividade < '2016-01-01') AS mais_10_anos,
        COUNT(*) AS total_setor
    FROM receita.estabelecimentos e
    LEFT JOIN cnae_postal_score c ON c.subclasse = e.cnae_fiscal_principal
    WHERE e.situacao_cadastral = '02'
    GROUP BY c.postal_categoria
    ORDER BY total_setor DESC
";
$rowsCruzado = $pdo->query($sqlCruzado)->fetchAll(PDO::FETCH_ASSOC);

printf("  %-15s | %-12s | %-12s | %-12s | %-12s\n", "Categoria", "<= 5 Anos", "6-10 Anos", "> 10 Anos", "Total Setor");
echo "  " . str_repeat("-", 72) . "\n";
foreach ($rowsCruzado as $r) {
    printf("  %-15s | %6d (%4.1f%%) | %6d (%4.1f%%) | %6d (%4.1f%%) | %6d\n",
        $r['categoria'],
        $r['ate_5_anos'], ($r['total_setor'] ? $r['ate_5_anos']*100/$r['total_setor'] : 0),
        $r['de_6_a_10_anos'], ($r['total_setor'] ? $r['de_6_a_10_anos']*100/$r['total_setor'] : 0),
        $r['mais_10_anos'], ($r['total_setor'] ? $r['mais_10_anos']*100/$r['total_setor'] : 0),
        $r['total_setor']
    );
}

echo "\n=== Simulação de Métrica Proposta de Score de Idade/Setor ===\n";
echo "Fórmula proposta:\n";
echo "  Score Tempo de Vida = Base_Idade * Multiplicador_Setor\n";
echo "  Base_Idade: 0-2 anos (100 pts), 3-5 anos (80 pts), 6-10 anos (50 pts), 11-15 anos (25 pts), >15 anos (10 pts)\n";
