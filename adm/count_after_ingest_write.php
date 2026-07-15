<?php
$out = __DIR__ . DIRECTORY_SEPARATOR . 'count_after_ingest_output.txt';
$fh = fopen($out,'wb');
$dsn = "pgsql:host=127.0.0.1;port=5432;dbname=spiv";
$user='postgres'; $pass='LulaTetra26';
try{
    $pdo=new PDO($dsn,$user,$pass,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
    fwrite($fh, "CONNECTED\n");
}catch(Exception $e){ fwrite($fh, "CONNECT ERROR: " . $e->getMessage() . "\n"); fclose($fh); exit(1); }
$tables=['empresas','estabelecimentos','socios','simples','cnaes','municipios'];
foreach($tables as $t){
    try{
        $stmt=$pdo->query("SELECT COUNT(*) as cnt FROM receita.\"$t\"");
        $cnt=$stmt->fetch(PDO::FETCH_ASSOC)['cnt'];
        fwrite($fh, "$t: $cnt\n");
    }catch(Exception $e){
        fwrite($fh, "$t: ERROR: " . $e->getMessage() . "\n");
    }
}
fclose($fh);
echo "WROTE $out\n";
