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

try {
    $pdo = new PDO("pgsql:host=$host;dbname=$dbname", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Query completa idêntica à do controller (busca por nome)
    $sql = "SELECT (e.cnpj_basico || e.cnpj_ordem || e.cnpj_dv) AS cnpj,
                   emp.razao_social, e.nome_fantasia,
                   e.tipo_logradouro, e.logradouro, e.numero, e.complemento, e.bairro, e.cep, e.uf,
                   m.descricao AS municipio_nome,
                   loc.latitude AS loc_lat, loc.longitude AS loc_lng,
                   cw.rfb_situacao_cadastral, cw.rfb_verificado_em,
                   COALESCE(ce.logistics_score, 0) AS logistics_score,
                   ce.score_breakdown,
                   (cr.cnpj IS NOT NULL) AS encarteirado,
                   cr.matricula_mcmcu AS vendedor_matricula,
                   COALESCE(vu.nome, cr.forca_vendas_nome, cr.matricula_mcmcu) AS vendedor_nome,
                   ra.status         AS ra_status,
                   ra.total          AS ra_total,
                   ra.pesquisado_em  AS ra_pesquisado_em
            FROM receita.estabelecimentos e
            LEFT JOIN receita.empresas emp ON e.cnpj_basico = emp.cnpj_basico
            LEFT JOIN receita.municipios m ON e.municipio = m.codigo
            LEFT JOIN client_locations loc ON loc.cnpj = (e.cnpj_basico || e.cnpj_ordem || e.cnpj_dv)
            LEFT JOIN client_wallets cw ON cw.cnpj = (e.cnpj_basico || e.cnpj_ordem || e.cnpj_dv)
            LEFT JOIN client_enrichment ce ON ce.cnpj = (e.cnpj_basico || e.cnpj_ordem || e.cnpj_dv)
            LEFT JOIN carteira_raw cr ON REGEXP_REPLACE(cr.cnpj, '[^0-9]', '', 'g') = (e.cnpj_basico || e.cnpj_ordem || e.cnpj_dv)
            LEFT JOIN vendor_users vu ON vu.matricula = cr.matricula_mcmcu
            LEFT JOIN client_ra_scans ra ON ra.cnpj = (e.cnpj_basico || e.cnpj_ordem || e.cnpj_dv)
            WHERE (LOWER(emp.razao_social) LIKE ?
               OR LOWER(e.nome_fantasia) LIKE ?
               OR LOWER(e.logradouro) LIKE ?
               OR LOWER(e.bairro) LIKE ?)
            ORDER BY CASE WHEN cr.cnpj IS NOT NULL THEN 1 ELSE 0 END ASC,
                     COALESCE(ce.logistics_score, 0) DESC
            LIMIT 5";

    $param = '%lapa%';
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$param, $param, $param, $param]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "QUERY COMPLETA OK: " . count($rows) . " row(s)\n";

} catch (Exception $e) {
    echo "ERRO NA QUERY COMPLETA: " . $e->getMessage() . "\n";
}

// Testa também a rota via curl para pegar o erro real da resposta HTTP
echo "\n--- Teste via HTTP ---\n";
$ch = curl_init('http://localhost/spiv/public/vendedor/prospectar/pesquisa/buscar?q=lapa');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
$resp = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$body = substr($resp, $headerSize);
curl_close($ch);
echo "HTTP Status: $httpCode\n";
echo "Body (primeiros 500 chars): " . substr($body, 0, 500) . "\n";
