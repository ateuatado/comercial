<?php
// Diagnóstico: estrutura da tabela receita.estabelecimentos
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

echo "=== Colunas de receita.estabelecimentos ===\n";
$stmt = $pdo->query("
    SELECT column_name, data_type, character_maximum_length
    FROM information_schema.columns
    WHERE table_schema = 'receita' AND table_name = 'estabelecimentos'
    ORDER BY ordinal_position
");
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $col) {
    printf("  %-40s %s\n", $col['column_name'], $col['data_type'] . ($col['character_maximum_length'] ? '('.$col['character_maximum_length'].')' : ''));
}

echo "\n=== Amostra de 3 linhas (campos de data) ===\n";
$stmt2 = $pdo->query("SELECT * FROM receita.estabelecimentos LIMIT 3");
$rows = $stmt2->fetchAll(PDO::FETCH_ASSOC);
if ($rows) {
    // Filtra colunas que parecem ser datas
    $dateCols = array_filter(array_keys($rows[0]), fn($c) => preg_match('/data|inicio|abertura|situac|cancelamento|baixada/i', $c));
    foreach ($rows as $i => $row) {
        echo "  Row $i: ";
        foreach ($dateCols as $col) {
            echo "$col={$row[$col]} | ";
        }
        echo "\n";
    }
}

echo "\n=== Colunas de receita.empresas ===\n";
$stmt3 = $pdo->query("
    SELECT column_name, data_type
    FROM information_schema.columns
    WHERE table_schema = 'receita' AND table_name = 'empresas'
    ORDER BY ordinal_position
");
foreach ($stmt3->fetchAll(PDO::FETCH_ASSOC) as $col) {
    printf("  %-40s %s\n", $col['column_name'], $col['data_type']);
}
