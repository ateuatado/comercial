<?php
$out = __DIR__ . DIRECTORY_SEPARATOR . 'count_estimates.txt';
$fh = fopen($out, 'wb');
$host = '127.0.0.1'; $db='spiv'; $user='postgres'; $pass='LulaTetra26';
$dsn = "pgsql:host=127.0.0.1;port=5432;dbname=$db";
try { $pdo = new PDO($dsn,$user,$pass,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]); fwrite($fh,"Connected\n"); }
catch(Exception $e){ fwrite($fh,"ERROR: " . $e->getMessage() . "\n"); fclose($fh); exit(1);} 
$sql = "SELECT c.relname AS table, COALESCE(c.reltuples,0)::bigint AS est_rows
FROM pg_class c
JOIN pg_namespace n ON n.oid = c.relnamespace
WHERE n.nspname = 'receita' AND c.relkind = 'r'
ORDER BY c.relname";
$stmt = $pdo->query($sql);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach($rows as $r){ fwrite($fh, "{$r['table']}: {$r['est_rows']}\n"); }
fclose($fh);
echo "Wrote $out\n";
