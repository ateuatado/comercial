<?php

/**
 * Script de importação e classificação automática do CNAE postal.
 *
 * Lê o CSV em adm/cnae_classificacao.csv, aplica regras de negócio para
 * atribuir postal_score (0–5) e postal_categoria a cada CNAE, e insere
 * tudo na tabela cnae_postal_score via UPSERT.
 *
 * Pode ser reexecutado sem risco — apenas atualiza os scores não revisados.
 *
 * Uso: php scratch/importar_cnae_postal.php
 */

// ── Bootstrap mínimo ───────────────────────────────────────────
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

// ── Regras de classificação ────────────────────────────────────

/**
 * Palavras-chave que ajustam o score base (aplicadas na denominação).
 * Formato: [palavra => ajuste_de_pontos]
 */
$keywordBoosts = [
    // +2 → alto potencial postal
    'varejo'          => +2,
    'varejista'       => +2,
    'loja'            => +2,
    'venda'           => +2,
    'venda a varejo'  => +2,
    'e-commerce'      => +3,
    'comércio'        => +2,
    'comercio'        => +2,
    'farmácia'        => +2,
    'farmacia'        => +2,
    'livraria'        => +2,
    'livros'          => +2,
    'vestuário'       => +2,
    'vestuario'       => +2,
    'confecção'       => +2,
    'confeccao'       => +2,
    'perfumaria'      => +2,
    'cosméticos'      => +2,
    'cosmeticos'      => +2,
    'eletrodoméstico' => +2,
    'eletrodomestico' => +2,
    'eletrônico'      => +2,
    'eletronico'      => +2,
    'brinquedo'       => +2,
    'calçado'         => +2,
    'calcado'         => +2,
    'papelaria'       => +2,
    'suprimentos'     => +1,
    'catálogo'        => +2,
    'catalogo'        => +2,

    // +1 → potencial médio
    'representante'   => +1,
    'distribuidor'    => +1,
    'distribuição'    => +1,
    'distribuicao'    => +1,
    'importador'      => +1,
    'exportador'      => +1,
    'atacado'         => +1,
    'atacadista'      => +1,
    'intermediário'   => +1,
    'intermediario'   => +1,
    'laboratório'     => +1,
    'laboratorio'     => +1,
    'ótica'           => +1,
    'otica'           => +1,

    // -1 → baixo potencial
    'construção civil'=> -1,
    'construcao'      => -1,
    'mineração'       => -1,
    'mineracao'       => -1,
    'extração'        => -1,
    'extracao'        => -1,
    'cultivo'         => -1,
    'pecuária'        => -1,
    'pecuaria'        => -1,
    'florestal'       => -1,
    'silvicultura'    => -1,
];

/**
 * Score base por seção IBGE (string normalizada).
 */
function scoreBaseBySecao(string $secao): array {
    $s = mb_strtolower($secao);

    if (str_contains($s, 'comércio') || str_contains($s, 'comercio'))
        return [3, 'varejo'];

    if (str_contains($s, 'indústria') || str_contains($s, 'industria') || str_contains($s, 'transformação'))
        return [2, 'industria'];

    if (str_contains($s, 'transporte') || str_contains($s, 'armazenagem'))
        return [1, 'servico'];  // concorrentes ou parceiros — baixo score

    if (str_contains($s, 'alojamento') || str_contains($s, 'alimentação') || str_contains($s, 'alimentacao'))
        return [1, 'servico'];

    if (str_contains($s, 'saúde') || str_contains($s, 'saude'))
        return [2, 'saude'];

    if (str_contains($s, 'educação') || str_contains($s, 'educacao'))
        return [1, 'educacao'];

    if (str_contains($s, 'informação') || str_contains($s, 'informacao') || str_contains($s, 'comunicação'))
        return [1, 'servico'];

    if (str_contains($s, 'profissional') || str_contains($s, 'científica') || str_contains($s, 'técnica'))
        return [1, 'servico'];

    if (str_contains($s, 'financeira') || str_contains($s, 'seguro'))
        return [1, 'servico'];

    if (str_contains($s, 'imobiliária') || str_contains($s, 'imobiliaria'))
        return [0, 'descarte'];

    if (str_contains($s, 'administração pública') || str_contains($s, 'administracao publica'))
        return [0, 'descarte'];

    if (str_contains($s, 'agricultura') || str_contains($s, 'pecuária') || str_contains($s, 'florestal'))
        return [1, 'agro'];

    if (str_contains($s, 'construção') || str_contains($s, 'construcao'))
        return [1, 'descarte'];

    if (str_contains($s, 'extrativa') || str_contains($s, 'extração'))
        return [0, 'descarte'];

    if (str_contains($s, 'arts') || str_contains($s, 'cultura') || str_contains($s, 'esporte') || str_contains($s, 'recreação'))
        return [1, 'servico'];

    if (str_contains($s, 'administrativas') || str_contains($s, 'serviços complementares'))
        return [1, 'servico'];

    if (str_contains($s, 'doméstico') || str_contains($s, 'domesrico'))
        return [0, 'descarte'];

    if (str_contains($s, 'organismo') || str_contains($s, 'extraterritorial'))
        return [0, 'descarte'];

    return [1, 'servico'];
}

/**
 * Regras especiais por prefixo de subclasse (primeiros 2 dígitos = divisão).
 */
function scoreBySubclassePrefix(string $sub, int $baseScore, string $baseCat): array {
    $div = substr($sub, 0, 2);
    $grp = substr($sub, 0, 4);

    // Todo varejo (divisão 47) → score mínimo 4
    if ($div === '47') return [max($baseScore, 4), 'varejo'];

    // Atacado (divisão 46) → score 3
    if ($div === '46') return [max($baseScore, 3), 'distribuicao'];

    // Fabricação que vende D2C: cosméticos, vestuário, calçados, brinquedos, eletrônicos
    if (in_array($div, ['20', '13', '14', '15', '26', '27', '32']))
        return [max($baseScore, 2), 'industria'];

    // Correios e outros transportes de encomenda (divisão 53) → 0 (concorrente)
    if ($div === '53') return [0, 'descarte'];

    // Farmácias e drogarias (grupo 4771)
    if ($grp === '4771') return [5, 'saude'];

    // Livrarias, papelarias (grupo 4761)
    if ($grp === '4761') return [4, 'varejo'];

    // E-commerce geral (grupo 4791)
    if ($grp === '4791') return [5, 'ecommerce'];

    // Laboratórios de análises clínicas (grupo 8640)
    if ($grp === '8640') return [max($baseScore, 2), 'saude'];

    // Ensino a distância (grupo 8599)
    if ($grp === '8599') return [max($baseScore, 2), 'educacao'];

    return [$baseScore, $baseCat];
}

// ── Leitura do CSV ─────────────────────────────────────────────
$csvPath = __DIR__ . '/../adm/cnae_classificacao.csv';
if (!file_exists($csvPath)) {
    die("❌ CSV não encontrado em: $csvPath\n");
}

$handle = fopen($csvPath, 'r');
$header = fgetcsv($handle); // pula cabeçalho

$rows    = [];
$total   = 0;
$skipped = 0;

while (($line = fgetcsv($handle)) !== false) {
    if (count($line) < 6) { $skipped++; continue; }

    // Decode ISO-8859-1 → UTF-8
    $line = array_map(fn($v) => mb_convert_encoding(trim($v, " \t\r\n\""), 'UTF-8', 'ISO-8859-1'), $line);

    [$secao, $divisao, $grupo, $classe, $subclasse, $denominacao] = $line;

    if (empty($subclasse) || !preg_match('/^\d{7}$/', $subclasse)) {
        $skipped++;
        continue;
    }

    // ── Aplicar regras ───────────────────────────────────────
    [$scoreBase, $catBase] = scoreBaseBySecao($secao);
    [$score, $cat]         = scoreBySubclassePrefix($subclasse, $scoreBase, $catBase);

    // Aplicar boosts por palavras-chave na denominação
    $denomLower = mb_strtolower($denominacao);
    $boostTotal = 0;
    $boostsUsados = [];
    foreach ($GLOBALS['keywordBoosts'] as $kw => $adj) {
        if (str_contains($denomLower, $kw)) {
            $boostTotal += $adj;
            $boostsUsados[] = "$kw($adj)";
        }
    }
    $score = max(0, min(5, $score + $boostTotal));

    // Refinar categoria com base no score final
    if ($score === 5 && $cat !== 'saude') $cat = 'ecommerce';
    if ($score >= 3 && $cat === 'industria') $cat = 'industria';

    $justificativa = "secao_base:{$scoreBase} sub_prefix_adj + boosts[" . implode(',', $boostsUsados) . "] => {$score}";

    $rows[] = [
        'subclasse'           => $subclasse,
        'denominacao'         => $denominacao,
        'secao'               => $secao,
        'divisao'             => $divisao,
        'grupo'               => $grupo,
        'classe'              => $classe,
        'postal_score'        => $score,
        'postal_categoria'    => $cat,
        'postal_justificativa'=> $justificativa,
    ];
    $total++;
}
fclose($handle);

echo "📂 Lidos: {$total} CNAEs | Ignorados: {$skipped}\n";

// ── UPSERT no banco ────────────────────────────────────────────
$stmt = $pdo->prepare("
    INSERT INTO cnae_postal_score
        (subclasse, denominacao, secao, divisao, grupo, classe,
         postal_score, postal_categoria, postal_justificativa)
    VALUES
        (:subclasse, :denominacao, :secao, :divisao, :grupo, :classe,
         :postal_score, :postal_categoria, :postal_justificativa)
    ON CONFLICT (subclasse) DO UPDATE
        SET denominacao          = EXCLUDED.denominacao,
            secao                = EXCLUDED.secao,
            divisao              = EXCLUDED.divisao,
            grupo                = EXCLUDED.grupo,
            classe               = EXCLUDED.classe,
            postal_score         = EXCLUDED.postal_score,
            postal_categoria     = EXCLUDED.postal_categoria,
            postal_justificativa = EXCLUDED.postal_justificativa,
            updated_at           = NOW()
        WHERE cnae_postal_score.revisado = FALSE
");

$inserted = 0;
$pdo->beginTransaction();
foreach ($rows as $row) {
    $stmt->execute($row);
    $inserted++;
}
$pdo->commit();

echo "✅ Importados/atualizados: {$inserted} CNAEs\n\n";

// ── Distribuição do score gerado ───────────────────────────────
echo "=== Distribuição por postal_score ===\n";
$dist = $pdo->query("
    SELECT postal_score, postal_categoria, COUNT(*) AS total
    FROM cnae_postal_score
    GROUP BY postal_score, postal_categoria
    ORDER BY postal_score DESC, total DESC
")->fetchAll(PDO::FETCH_ASSOC);

foreach ($dist as $d) {
    $bar = str_repeat('█', (int)round($d['total'] / 5));
    printf("  Score %d | %-15s | %4d CNAEs  %s\n",
        $d['postal_score'], $d['postal_categoria'], $d['total'], $bar);
}

echo "\n=== Top 20 CNAEs com maior score ===\n";
$top = $pdo->query("
    SELECT subclasse, postal_score, postal_categoria, denominacao
    FROM cnae_postal_score
    ORDER BY postal_score DESC, subclasse
    LIMIT 20
")->fetchAll(PDO::FETCH_ASSOC);
foreach ($top as $r) {
    printf("  [%d] %s | %-12s | %s\n",
        $r['postal_score'], $r['subclasse'], $r['postal_categoria'], mb_substr($r['denominacao'], 0, 60));
}
