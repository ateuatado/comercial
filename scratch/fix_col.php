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
$pdo->exec("ALTER TABLE prospect_scores ALTER COLUMN razao_capital TYPE NUMERIC(12,2)");
echo "Column altered successfully.\n";
