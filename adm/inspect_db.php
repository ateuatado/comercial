<?php
// Script temporário para inspecionar o banco Postgres local
$host = '127.0.0.1';
$port = 5432;
$db   = 'spiv';
$user = 'postgres';
$pass = 'LulaTetra26';
$dsn = "pgsql:host=$host;port=$port;dbname=$db";

try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_TIMEOUT => 5,
    ]);
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . PHP_EOL;
    exit(1);
}

// Get user tables (exclude pg_catalog and information_schema)
$stmt = $pdo->query("SELECT table_schema, table_name FROM information_schema.tables WHERE table_type='BASE TABLE' AND table_schema NOT IN ('pg_catalog','information_schema') ORDER BY table_schema, table_name");
$tables = $stmt->fetchAll(PDO::FETCH_ASSOC);
if (!$tables) {
    echo "No user tables found.\n";
    exit(0);
}

foreach ($tables as $t) {
    $schema = $t['table_schema'];
    $table = $t['table_name'];
    echo "TABLE: {$schema}.{$table}\n";

    // Columns
    $colStmt = $pdo->prepare("SELECT column_name, data_type, is_nullable FROM information_schema.columns WHERE table_schema = :schema AND table_name = :table ORDER BY ordinal_position");
    $colStmt->execute(['schema'=>$schema, 'table'=>$table]);
    $cols = $colStmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($cols as $c) {
        echo "  - {$c['column_name']} ({$c['data_type']}) nullable: {$c['is_nullable']}\n";
    }

    // Row count (fast approximate using COUNT)
    try {
        $countStmt = $pdo->query("SELECT COUNT(*) AS cnt FROM \"$schema\".\"$table\"");
        $cnt = $countStmt->fetch(PDO::FETCH_ASSOC)['cnt'];
        echo "  Rows: {$cnt}\n";
    } catch (Exception $e) {
        echo "  Rows: (count error: {$e->getMessage()})\n";
    }

    // Sample rows
    try {
        $sampleStmt = $pdo->query("SELECT * FROM \"$schema\".\"$table\" LIMIT 5");
        $rows = $sampleStmt->fetchAll(PDO::FETCH_ASSOC);
        if ($rows) {
            echo "  Sample rows:\n";
            foreach ($rows as $r) {
                $cols = array_map(function($k,$v){return "$k: " . (is_null($v)?'NULL':(is_scalar($v)?$v:json_encode($v)));}, array_keys($r), $r);
                echo "    - " . implode(', ', $cols) . "\n";
            }
        } else {
            echo "  Sample rows: (none)\n";
        }
    } catch (Exception $e) {
        echo "  Sample rows: (error: {$e->getMessage()})\n";
    }

    echo str_repeat('-',40) . PHP_EOL;
}

echo "Done.\n";
