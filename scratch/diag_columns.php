<?php
chdir(__DIR__ . '/..');
$env = file('.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
foreach ($env as $l) {
    if ($l[0] !== '#' && strpos($l, '=') !== false) {
        [$k, $v] = explode('=', $l, 2);
        putenv(trim($k) . '=' . trim($v));
    }
}
$pdo = new PDO(
    'pgsql:host=' . getenv('database.default.hostname') . ';dbname=' . getenv('database.default.database'),
    getenv('database.default.username'),
    getenv('database.default.password')
);

foreach (['client_wallets', 'wallet_movements', 'vendor_movements'] as $table) {
    echo "\n=== $table ===\n";
    $rows = $pdo->query("
        SELECT column_name, data_type, character_maximum_length
        FROM information_schema.columns
        WHERE table_name = '$table'
        ORDER BY ordinal_position
    ")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $r) {
        printf("  %-30s  %-20s  %s\n", $r['column_name'], $r['data_type'], $r['character_maximum_length'] ?? '—');
    }
}
