<?php
// Diagnóstico: consistência do campo gerencia em vendor_users
// Executar: php scratch/diag_gerencia.php

define('FCPATH', __DIR__ . '/../public/');
chdir(__DIR__ . '/..');

require 'vendor/autoload.php';

$env = file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
foreach ($env as $line) {
    if (strpos($line, '=') !== false && $line[0] !== '#') {
        [$k, $v] = explode('=', $line, 2);
        putenv(trim($k) . '=' . trim($v));
    }
}

$host   = getenv('database.default.hostname') ?: 'localhost';
$db     = getenv('database.default.database') ?: 'spivvps';
$user   = getenv('database.default.username') ?: 'postgres';
$pass   = getenv('database.default.password') ?: '';
$port   = getenv('database.default.port')     ?: '5432';

$dsn = "pgsql:host=$host;port=$port;dbname=$db";
$pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

echo "\n=== DIAGNÓSTICO: campo 'gerencia' em vendor_users ===\n\n";

// 1. Totais gerais
$r = $pdo->query("
    SELECT
        COUNT(*)                         AS total,
        COUNT(gerencia)                  AS com_gerencia,
        COUNT(*) - COUNT(gerencia)       AS sem_gerencia,
        COUNT(DISTINCT gerencia)         AS gerencias_distintas,
        COUNT(DISTINCT se)               AS ses_distintas,
        COUNT(DISTINCT mtr_coordenador)  AS coordenadores
    FROM vendor_users
    WHERE ativo = true
")->fetch(PDO::FETCH_ASSOC);

echo "Vendedores ativos:        {$r['total']}\n";
echo "Com gerência preenchida:  {$r['com_gerencia']}\n";
echo "SEM gerência:             {$r['sem_gerencia']}\n";
echo "Gerências distintas:      {$r['gerencias_distintas']}\n";
echo "SEs distintas:            {$r['ses_distintas']}\n";
echo "Coordenadores distintos:  {$r['coordenadores']}\n";

// 2. Coordenadores com gerência NULL
$semGerCoord = $pdo->query("
    SELECT COUNT(*) AS n FROM vendor_users
    WHERE ativo = true AND mtr_coordenador IS NOT NULL AND gerencia IS NULL
")->fetchColumn();
echo "\nVendedores com coordenador mas SEM gerência: $semGerCoord\n";

// 3. Distribuição por gerência (top 15)
echo "\n--- Top 15 gerências por volume de vendedores ---\n";
$rows = $pdo->query("
    SELECT COALESCE(gerencia, '(NULL)') AS gerencia, COUNT(*) AS total
    FROM vendor_users WHERE ativo = true
    GROUP BY gerencia ORDER BY total DESC LIMIT 15
")->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $row) {
    printf("  %-50s  %d\n", $row['gerencia'], $row['total']);
}

// 4. Coordenadores e suas gerências (consistência: coord deveria ter 1 gerência)
echo "\n--- Coordenadores com múltiplas gerências nos subordinados (inconsistência) ---\n";
$rows = $pdo->query("
    SELECT mtr_coordenador, COUNT(DISTINCT gerencia) AS n_gerencias,
           STRING_AGG(DISTINCT gerencia, ' | ') AS gerencias
    FROM vendor_users
    WHERE ativo = true AND mtr_coordenador IS NOT NULL
    GROUP BY mtr_coordenador
    HAVING COUNT(DISTINCT gerencia) > 1
    ORDER BY n_gerencias DESC
    LIMIT 10
")->fetchAll(PDO::FETCH_ASSOC);
if (empty($rows)) {
    echo "  Nenhuma inconsistência encontrada.\n";
} else {
    foreach ($rows as $r) {
        echo "  Coord {$r['mtr_coordenador']}: {$r['n_gerencias']} gerências → {$r['gerencias']}\n";
    }
}

// 5. Coordenadores sem gerência própria
echo "\n--- Coordenadores (mtr_coordenador) sem gerência definida ---\n";
$rows = $pdo->query("
    SELECT DISTINCT v.mtr_coordenador, c.gerencia AS gerencia_coord
    FROM vendor_users v
    LEFT JOIN vendor_users c ON c.matricula = v.mtr_coordenador
    WHERE v.ativo = true AND v.mtr_coordenador IS NOT NULL AND c.gerencia IS NULL
    LIMIT 10
")->fetchAll(PDO::FETCH_ASSOC);
if (empty($rows)) {
    echo "  Todos os coordenadores têm gerência definida.\n";
} else {
    foreach ($rows as $r) {
        echo "  Coord {$r['mtr_coordenador']}: SEM gerência\n";
    }
}

echo "\n=== FIM DO DIAGNÓSTICO ===\n\n";
