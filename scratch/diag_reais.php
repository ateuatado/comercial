<?php
// Diagnóstico: dados reais vs fictícios em vendor_users
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

$total   = $pdo->query("SELECT COUNT(*) FROM vendor_users")->fetchColumn();
$ficticio = $pdo->query("SELECT COUNT(*) FROM vendor_users WHERE matricula LIKE 'V0%' OR matricula LIKE 'C0%'")->fetchColumn();
$reais   = $total - $ficticio;

echo "Total vendor_users:                 $total\n";
echo "Registros fictícios (demo V0/C0):   $ficticio\n";
echo "Registros reais (importados):       $reais\n\n";

if ($reais > 0) {
    $comGer = $pdo->query(
        "SELECT COUNT(gerencia) FROM vendor_users WHERE matricula NOT LIKE 'V0%' AND matricula NOT LIKE 'C0%'"
    )->fetchColumn();
    $semGer = $reais - $comGer;
    $pct    = $reais > 0 ? round($comGer / $reais * 100, 1) : 0;
    echo "Reais COM gerência:  $comGer  ($pct%)\n";
    echo "Reais SEM gerência:  $semGer\n";

    $distintas = $pdo->query(
        "SELECT COUNT(DISTINCT gerencia) FROM vendor_users WHERE matricula NOT LIKE 'V0%' AND matricula NOT LIKE 'C0%'"
    )->fetchColumn();
    echo "Gerências distintas nos reais: $distintas\n";
} else {
    echo "(Nenhum registro real — base ainda é somente demo)\n";
}
