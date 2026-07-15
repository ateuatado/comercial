<?php
$host = '127.0.0.1';
$port = 5432;
$db   = 'spiv';
$user = 'postgres';
$pass = 'LulaTetra26';
$dsn = "pgsql:host=$host;port=$port;dbname=$db";
$outFile = __DIR__ . DIRECTORY_SEPARATOR . 'db_summary2_output.txt';
$fh = fopen($outFile, 'wb');
if (!$fh) { echo "Failed to open output file\n"; exit(1); }
fwrite($fh, "DB Summary (schema=receita)\n\n");
try {
    $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
    $pdo->exec("SET NAMES 'UTF8'");
} catch (Exception $e) {
    fwrite($fh, "ERROR CONNECT: " . $e->getMessage() . "\n");
    fclose($fh);
    exit(1);
}
try {
    $stmt = $pdo->prepare("SELECT table_name FROM information_schema.tables WHERE table_schema = 'receita' AND table_type='BASE TABLE' ORDER BY table_name");
    $stmt->execute();
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    foreach ($tables as $t) {
        try {
            $cstmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM receita.\"$t\"");
            $cstmt->execute();
            $cnt = $cstmt->fetch(PDO::FETCH_ASSOC)['cnt'];
            fwrite($fh, "receita.$t: $cnt\n");
        } catch (Exception $e) {
            fwrite($fh, "receita.$t: error: " . $e->getMessage() . "\n");
        }
    }
    fwrite($fh, "\nRecent log_ingestao:\n");
    try {
        $l = $pdo->query("SELECT tabela, registros_antes, registros_novos, registros_total, status, to_char(created_at,'YYYY-MM-DD HH24:MI:SS') as whenat FROM receita.log_ingestao ORDER BY id DESC LIMIT 20");
        $rows = $l->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $r) {
            fwrite($fh, "  {$r['whenat']} | {$r['tabela']} | antes={$r['registros_antes']} novos={$r['registros_novos']} total={$r['registros_total']} status={$r['status']}\n");
        }
    } catch (Exception $e) {
        fwrite($fh, "log_ingestao error: " . $e->getMessage() . "\n");
    }
} catch (Exception $e) {
    fwrite($fh, "ERROR: " . $e->getMessage() . "\n");
}
fclose($fh);
echo "Wrote $outFile\n";
