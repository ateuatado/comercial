<?php

$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    foreach (file($envFile) as $line) {
        $line = trim($line);
        if ($line && strpos($line, '#') !== 0 && strpos($line, '=') !== false) {
            [$key, $val] = explode('=', $line, 2);
            putenv(trim($key) . '=' . trim($val));
        }
    }
}

$host   = getenv('database.default.hostname') ?: 'localhost';
$dbname = getenv('database.default.database') ?: 'spiv';
$user   = getenv('database.default.username') ?: 'postgres';
$pass   = getenv('database.default.password') ?: '';

$pdo = new PDO("pgsql:host=$host;dbname=$dbname", $user, $pass);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "=== 1. Diagnóstico da Coluna Email nos Estabelecimentos Ativos ===\n\n";

$sqlEmailStats = "
    SELECT 
        COUNT(*) AS total_ativas,
        COUNT(*) FILTER (WHERE email IS NOT NULL AND TRIM(email) <> '') AS com_email,
        COUNT(*) FILTER (WHERE email IS NULL OR TRIM(email) = '') AS sem_email
    FROM receita.estabelecimentos
    WHERE situacao_cadastral = '02'
";
$stats = $pdo->query($sqlEmailStats)->fetch(PDO::FETCH_ASSOC);

printf("Total Ativas: %d | Com E-mail: %d (%5.2f%%) | Sem E-mail: %d (%5.2f%%)\n\n",
    $stats['total_ativas'],
    $stats['com_email'], $stats['com_email'] * 100.0 / $stats['total_ativas'],
    $stats['sem_email'], $stats['sem_email'] * 100.0 / $stats['total_ativas']
);

echo "=== 2. Top 25 Domínios de E-mail Mais Frequentes ===\n\n";

$sqlTopDomains = "
    SELECT 
        LOWER(SUBSTRING(email FROM '@(.*)$')) AS dominio,
        COUNT(*) AS total
    FROM receita.estabelecimentos
    WHERE situacao_cadastral = '02'
      AND email IS NOT NULL 
      AND TRIM(email) <> ''
      AND email LIKE '%@%'
    GROUP BY LOWER(SUBSTRING(email FROM '@(.*)$'))
    ORDER BY total DESC
    LIMIT 25
";

$domains = $pdo->query($sqlTopDomains)->fetchAll(PDO::FETCH_ASSOC);

printf("%-35s | %-10s\n", "Domínio", "Total empresas");
echo str_repeat("-", 50) . "\n";
foreach ($domains as $d) {
    printf("%-35s | %10d\n", $d['dominio'], $d['total']);
}

echo "\n=== 3. Classificação: Domínio Próprio Corporativo vs Webmail Gratuito ===\n\n";

$sqlClassificacaoEmail = "
    WITH emails_classificados AS (
        SELECT 
            (e.cnpj_basico || e.cnpj_ordem || e.cnpj_dv) AS cnpj,
            e.email,
            LOWER(TRIM(SUBSTRING(e.email FROM '@(.*)$'))) AS dominio,
            CASE 
                WHEN e.email IS NULL OR TRIM(e.email) = '' OR e.email NOT LIKE '%@%' THEN 'sem_email'
                WHEN LOWER(TRIM(SUBSTRING(e.email FROM '@(.*)$'))) IN (
                    'gmail.com', 'hotmail.com', 'outlook.com', 'yahoo.com', 'yahoo.com.br',
                    'bol.com.br', 'uol.com.br', 'ig.com.br', 'terra.com.br', 'globo.com',
                    'globomail.com', 'icloud.com', 'me.com', 'msn.com', 'live.com', 'oi.com.br'
                ) THEN 'webmail_pessoal'
                ELSE 'dominio_proprio'
            END AS tipo_email
        FROM receita.estabelecimentos e
        WHERE e.situacao_cadastral = '02'
    )
    SELECT 
        tipo_email,
        COUNT(*) AS total,
        ROUND(COUNT(*) * 100.0 / SUM(COUNT(*)) OVER(), 2) AS pct
    FROM emails_classificados
    GROUP BY tipo_email
    ORDER BY total DESC
";

$classif = $pdo->query($sqlClassificacaoEmail)->fetchAll(PDO::FETCH_ASSOC);
foreach ($classif as $c) {
    printf("  %-20s: %6d empresas (%5.2f%%)\n", $c['tipo_email'], $c['total'], $c['pct']);
}
