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

echo "======================================================\n";
echo " ANÁLISE DE IDADE DAS EMPRESAS — receita.estabelecimentos\n";
echo "======================================================\n\n";

// ── 1. Métricas gerais por situação cadastral ─────────────────────────────
echo "=== 1. Distribuição geral por situação cadastral ===\n";
$sql1 = "
    SELECT
        situacao_cadastral,
        CASE situacao_cadastral
            WHEN '01' THEN 'Nula'
            WHEN '02' THEN 'Ativa'
            WHEN '03' THEN 'Suspensa'
            WHEN '04' THEN 'Inapta'
            WHEN '08' THEN 'Baixada'
            ELSE 'Outra (' || situacao_cadastral || ')'
        END AS situacao_label,
        COUNT(*) AS total,
        ROUND(COUNT(*) * 100.0 / SUM(COUNT(*)) OVER (), 1) AS pct
    FROM receita.estabelecimentos
    WHERE data_inicio_atividade ~ '^[0-9]{8}$'
    GROUP BY situacao_cadastral
    ORDER BY total DESC
";
$rows = $pdo->query($sql1)->fetchAll(PDO::FETCH_ASSOC);
printf("  %-12s %-15s %12s %6s\n", 'Código', 'Situação', 'Total', '%');
printf("  %s\n", str_repeat('-', 50));
foreach ($rows as $r) {
    printf("  %-12s %-15s %12s %5s%%\n",
        $r['situacao_cadastral'], $r['situacao_label'],
        number_format($r['total'], 0, ',', '.'), $r['pct']);
}

// ── 2. Estatísticas de idade por situação cadastral ──────────────────────
echo "\n=== 2. Idade média, mediana, mín e máx (anos) por situação ===\n";
$sql2 = "
    WITH idades AS (
        SELECT
            situacao_cadastral,
            CASE situacao_cadastral
                WHEN '01' THEN 'Nula'
                WHEN '02' THEN 'Ativa'
                WHEN '03' THEN 'Suspensa'
                WHEN '04' THEN 'Inapta'
                WHEN '08' THEN 'Baixada'
                ELSE 'Outra'
            END AS sit_label,
            DATE_PART('year', AGE(
                CURRENT_DATE,
                TO_DATE(data_inicio_atividade, 'YYYYMMDD')
            )) AS anos
        FROM receita.estabelecimentos
        WHERE data_inicio_atividade ~ '^[0-9]{8}$'
          AND data_inicio_atividade != '00000000'
          AND data_inicio_atividade > '19500101'
          AND LENGTH(data_inicio_atividade) = 8
    )
    SELECT
        situacao_cadastral,
        sit_label,
        COUNT(*)                             AS total,
        ROUND(AVG(anos)::numeric, 1)                 AS media_anos,
        PERCENTILE_CONT(0.5) WITHIN GROUP (ORDER BY anos) AS mediana_anos,
        MIN(anos)                            AS min_anos,
        MAX(anos)                            AS max_anos,
        ROUND(STDDEV(anos)::numeric, 1)     AS desvio_padrao
    FROM idades
    GROUP BY situacao_cadastral, sit_label
    ORDER BY total DESC
";
$rows2 = $pdo->query($sql2)->fetchAll(PDO::FETCH_ASSOC);
printf("  %-8s %-12s %10s %8s %8s %6s %6s %8s\n",
    'Cód', 'Situação', 'Total', 'Média', 'Mediana', 'Mín', 'Máx', 'Desvio');
printf("  %s\n", str_repeat('-', 72));
foreach ($rows2 as $r) {
    printf("  %-8s %-12s %10s %7sa %7sa %5sa %5sa %7sa\n",
        $r['situacao_cadastral'], $r['sit_label'],
        number_format($r['total'], 0, ',', '.'),
        $r['media_anos'], $r['mediana_anos'],
        $r['min_anos'], $r['max_anos'], $r['desvio_padrao']);
}

// ── 3. Distribuição por faixas de idade — só ATIVAS ──────────────────────
echo "\n=== 3. CNPJs ATIVOS por faixa de idade ===\n";
$sql3 = "
    SELECT
        CASE
            WHEN DATE_PART('year', AGE(CURRENT_DATE, TO_DATE(data_inicio_atividade, 'YYYYMMDD'))) < 1  THEN '< 1 ano'
            WHEN DATE_PART('year', AGE(CURRENT_DATE, TO_DATE(data_inicio_atividade, 'YYYYMMDD'))) < 2  THEN '1-2 anos'
            WHEN DATE_PART('year', AGE(CURRENT_DATE, TO_DATE(data_inicio_atividade, 'YYYYMMDD'))) < 3  THEN '2-3 anos'
            WHEN DATE_PART('year', AGE(CURRENT_DATE, TO_DATE(data_inicio_atividade, 'YYYYMMDD'))) < 5  THEN '3-5 anos'
            WHEN DATE_PART('year', AGE(CURRENT_DATE, TO_DATE(data_inicio_atividade, 'YYYYMMDD'))) < 8  THEN '5-8 anos'
            WHEN DATE_PART('year', AGE(CURRENT_DATE, TO_DATE(data_inicio_atividade, 'YYYYMMDD'))) < 12 THEN '8-12 anos'
            WHEN DATE_PART('year', AGE(CURRENT_DATE, TO_DATE(data_inicio_atividade, 'YYYYMMDD'))) < 20 THEN '12-20 anos'
            ELSE '20+ anos'
        END AS faixa,
        COUNT(*) AS total,
        ROUND(COUNT(*) * 100.0 / SUM(COUNT(*)) OVER (), 1) AS pct
    FROM receita.estabelecimentos
    WHERE situacao_cadastral = '02'
      AND data_inicio_atividade ~ '^[0-9]{8}$'
      AND data_inicio_atividade != '00000000'
      AND data_inicio_atividade > '19500101'
    GROUP BY faixa
    ORDER BY MIN(DATE_PART('year', AGE(CURRENT_DATE, TO_DATE(data_inicio_atividade, 'YYYYMMDD'))))
";
$rows3 = $pdo->query($sql3)->fetchAll(PDO::FETCH_ASSOC);
printf("  %-14s %12s %6s\n", 'Faixa', 'Total', '%');
printf("  %s\n", str_repeat('-', 36));
foreach ($rows3 as $r) {
    $bar = str_repeat('█', (int)round($r['pct'] / 2));
    printf("  %-14s %12s %5s%% %s\n",
        $r['faixa'],
        number_format($r['total'], 0, ',', '.'),
        $r['pct'], $bar);
}

// ── 4. Taxa de cancelamento por faixa de idade ───────────────────────────
echo "\n=== 4. Taxa de cancelamento (Baixada) por faixa de abertura ===\n";
$sql4 = "
    SELECT
        CASE
            WHEN DATE_PART('year', AGE(CURRENT_DATE, TO_DATE(data_inicio_atividade, 'YYYYMMDD'))) < 3  THEN '0-3 anos'
            WHEN DATE_PART('year', AGE(CURRENT_DATE, TO_DATE(data_inicio_atividade, 'YYYYMMDD'))) < 5  THEN '3-5 anos'
            WHEN DATE_PART('year', AGE(CURRENT_DATE, TO_DATE(data_inicio_atividade, 'YYYYMMDD'))) < 10 THEN '5-10 anos'
            WHEN DATE_PART('year', AGE(CURRENT_DATE, TO_DATE(data_inicio_atividade, 'YYYYMMDD'))) < 20 THEN '10-20 anos'
            ELSE '20+ anos'
        END AS faixa,
        COUNT(*) AS total,
        SUM(CASE WHEN situacao_cadastral = '02' THEN 1 ELSE 0 END) AS ativas,
        SUM(CASE WHEN situacao_cadastral = '08' THEN 1 ELSE 0 END) AS baixadas,
        ROUND(
            SUM(CASE WHEN situacao_cadastral = '08' THEN 1 ELSE 0 END) * 100.0 / COUNT(*),
        1) AS pct_baixada,
        ROUND(
            SUM(CASE WHEN situacao_cadastral = '02' THEN 1 ELSE 0 END) * 100.0 / COUNT(*),
        1) AS pct_ativa
    FROM receita.estabelecimentos
    WHERE data_inicio_atividade ~ '^[0-9]{8}$'
      AND data_inicio_atividade != '00000000'
      AND data_inicio_atividade > '19500101'
    GROUP BY faixa
    ORDER BY MIN(DATE_PART('year', AGE(CURRENT_DATE, TO_DATE(data_inicio_atividade, 'YYYYMMDD'))))
";
$rows4 = $pdo->query($sql4)->fetchAll(PDO::FETCH_ASSOC);
printf("  %-12s %12s %12s %12s %8s %8s\n", 'Faixa', 'Total', 'Ativas', 'Baixadas', '% Baixada', '% Ativa');
printf("  %s\n", str_repeat('-', 70));
foreach ($rows4 as $r) {
    printf("  %-12s %12s %12s %12s %8s%% %8s%%\n",
        $r['faixa'],
        number_format($r['total'], 0, ',', '.'),
        number_format($r['ativas'], 0, ',', '.'),
        number_format($r['baixadas'], 0, ',', '.'),
        $r['pct_baixada'], $r['pct_ativa']);
}

// ── 5. Volume de ATIVOS por ano de abertura (últimos 15 anos) ────────────
echo "\n=== 5. Volume de ATIVOS por ano de abertura (últimos 15 anos) ===\n";
$sql5 = "
    SELECT
        SUBSTRING(data_inicio_atividade, 1, 4) AS ano_abertura,
        COUNT(*) AS total_ativas
    FROM receita.estabelecimentos
    WHERE situacao_cadastral = '02'
      AND data_inicio_atividade ~ '^[0-9]{8}$'
      AND data_inicio_atividade >= '20100101'
    GROUP BY ano_abertura
    ORDER BY ano_abertura
";
$rows5 = $pdo->query($sql5)->fetchAll(PDO::FETCH_ASSOC);
$max5 = max(array_column($rows5, 'total_ativas'));
printf("  %-6s %12s %s\n", 'Ano', 'Total Ativas', 'Barra');
printf("  %s\n", str_repeat('-', 60));
foreach ($rows5 as $r) {
    $bar = str_repeat('▓', (int)round($r['total_ativas'] / $max5 * 30));
    printf("  %-6s %12s %s\n",
        $r['ano_abertura'],
        number_format($r['total_ativas'], 0, ',', '.'),
        $bar);
}

echo "\n======================================================\n";
echo " FIM DA ANÁLISE\n";
echo "======================================================\n";
