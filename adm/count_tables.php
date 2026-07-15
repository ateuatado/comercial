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
    echo "ERROR CONNECT: " . $e->getMessage() . PHP_EOL;
    exit(1);
}
$tables = ['empresas','estabelecimentos','socios','simples'];
foreach ($tables as $t) {
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM receita.\"$t\"");
        $cnt = $stmt->fetch(PDO::FETCH_ASSOC)['cnt'];
        echo "$t: $cnt\n";
    } catch (Exception $e) {
        echo "$t: error: " . $e->getMessage() . "\n";
    }
}
