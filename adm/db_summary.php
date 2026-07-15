<?php
$host = '127.0.0.1';
$port = 5432;
$db   = 'spiv';
$user = 'postgres';
$pass = 'LulaTetra26';
$dsn = "pgsql:host=$host;port=$port;dbname=$db";
try {
    $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . PHP_EOL;
    exit(1);
}
$tables = [
    'receita.cnaes','receita.motivos','receita.municipios','receita.naturezas','receita.paises','receita.qualificacoes',
    'receita.empresas','receita.estabelecimentos','receita.socios','receita.simples','receita.log_ingestao'
];
foreach ($tables as $t) {
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM $t");
        $cnt = $stmt->fetch(PDO::FETCH_ASSOC)['cnt'];
        echo "$t: $cnt\n";
    } catch (Exception $e) {
        echo "$t: (error: {$e->getMessage()})\n";
    }
}
// Also list recent log_ingestao entries
try {
    $stmt = $pdo->query("SELECT tabela, registros_antes, registros_novos, registros_total, status, to_char(created_at,'YYYY-MM-DD HH24:MI:SS') as whenat FROM receita.log_ingestao ORDER BY id DESC LIMIT 10");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "\nRecent log_ingestao:\n";
    if ($rows) {
        foreach ($rows as $r) {
            echo "  {$r['whenat']} | {$r['tabela']} | antes={$r['registros_antes']} novos={$r['registros_novos']} total={$r['registros_total']} status={$r['status']}\n";
        }
    } else {
        echo "  (none)\n";
    }
} catch (Exception $e) {
    echo "log_ingestao query error: {$e->getMessage()}\n";
}
