<?php
$outFile = __DIR__ . DIRECTORY_SEPARATOR . 'count_tables_output.txt';
$fh = fopen($outFile, 'wb');
if (!$fh) { echo "Failed to open output file\n"; exit(1); }
$host = '127.0.0.1';
$port = 5432;
$db   = 'spiv';
$user = 'postgres';
$pass = 'LulaTetra26';
$dsn = "pgsql:host=$host;port=$port;dbname=$db";
try {
    $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
    fwrite($fh, "Connected\n");
} catch (Exception $e) {
    fwrite($fh, "ERROR CONNECT: " . $e->getMessage() . "\n");
    fclose($fh);
    exit(1);
}
$tables = ['empresas','estabelecimentos','socios','simples'];
foreach ($tables as $t) {
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM receita.\"$t\"");
        $cnt = $stmt->fetch(PDO::FETCH_ASSOC)['cnt'];
        fwrite($fh, "$t: $cnt\n");
    } catch (Exception $e) {
        fwrite($fh, "$t: error: " . $e->getMessage() . "\n");
    }
}
fclose($fh);
echo "Wrote output to $outFile\n";
