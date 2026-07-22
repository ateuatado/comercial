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
    getenv('database.default.password'),
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

echo "\n=== CONTAS DE DEMO ===\n\n";
$rows = $pdo->query("
    SELECT matricula, nome, perfil_vendedor, se, gerencia, mtr_coordenador
    FROM vendor_users ORDER BY id LIMIT 25
")->fetchAll(PDO::FETCH_ASSOC);

foreach ($rows as $r) {
    $coord = $r['mtr_coordenador'] ? "coord={$r['mtr_coordenador']}" : "COORDENADOR";
    printf("%-8s  %-14s  %-5s  %-28s  %s\n",
        $r['matricula'], $r['perfil_vendedor'], $r['se'], $r['gerencia'] ?? '—', $coord);
}

echo "\n=== TOTAL POR COORDENADOR ===\n\n";
$tots = $pdo->query("
    SELECT mtr_coordenador, COUNT(*) as n
    FROM vendor_users
    WHERE mtr_coordenador IS NOT NULL AND ativo = true
    GROUP BY mtr_coordenador ORDER BY mtr_coordenador
")->fetchAll(PDO::FETCH_ASSOC);
foreach ($tots as $t) {
    echo "  Coord {$t['mtr_coordenador']}: {$t['n']} vendedores\n";
}

echo "\n=== CLIENTES POR GERÊNCIA (carteira_raw) ===\n\n";
$gers = $pdo->query("
    SELECT COALESCE(gerencia,'(sem gerencia)') AS g, COUNT(*) AS n
    FROM carteira_raw GROUP BY gerencia ORDER BY n DESC LIMIT 10
")->fetchAll(PDO::FETCH_ASSOC);
foreach ($gers as $g) {
    printf("  %-35s  %d clientes\n", $g['g'], $g['n']);
}
