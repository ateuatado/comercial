<?php
/**
 * Script de Diagnóstico do Serper.dev para o SPIV
 * Execute no terminal do VPS: php diagnosticar_serper.php
 */

echo "=== INICIANDO DIAGNÓSTICO DO SERPER.DEV NO VPS ===\n\n";

// 1. Verificar se a extensão cURL está ativa
if (!extension_loaded('curl')) {
    echo "❌ ERRO: A extensão PHP cURL não está ativa neste servidor VPS.\n";
    echo "Instale/habilite o cURL no PHP (ex: apt-get install php-curl) para que as requisições funcionem.\n";
    exit(1);
}
echo "✅ Extensão PHP cURL está ativa.\n";

// 2. Carregar a chave de API do .env
$apiKey = '';
$envPath = __DIR__ . '/.env';
if (file_exists($envPath)) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($key, $val) = explode('=', $line, 2);
            $key = trim($key);
            $val = trim($val);
            $val = trim($val, "\"'");
            
            if ($key === 'serper.apiKey' || $key === 'SERPER_API_KEY') {
                $apiKey = $val;
            }
        }
    }
}

if (empty($apiKey)) {
    echo "❌ ERRO: Nenhuma chave de API (serper.apiKey ou SERPER_API_KEY) foi encontrada no seu .env do VPS.\n";
    echo "Caminho verificado: {$envPath}\n";
    exit(1);
}

echo "✅ Chave de API encontrada no .env: " . substr($apiKey, 0, 5) . "..." . substr($apiKey, -5) . "\n";

// 3. Testar a conexão com o domínio google.serper.dev
echo "\nTestando resolução de DNS e conexão física com google.serper.dev...\n";
$ip = gethostbyname('google.serper.dev');
if ($ip === 'google.serper.dev') {
    echo "❌ ERRO: Não foi possível resolver o DNS do google.serper.dev. O servidor VPS parece estar sem acesso de saída à internet ou com problemas de DNS.\n";
} else {
    echo "✅ DNS resolvido com sucesso. IP de destino: {$ip}\n";
}

// 4. Efetuar requisição cURL para a API do Serper
$url = 'https://google.serper.dev/search';
$data = [
    'q'  => 'Correios Brasília',
    'gl' => 'br',
    'hl' => 'pt-br'
];

echo "Efetuando requisição POST para {$url}...\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'X-API-KEY: ' . $apiKey,
    'Content-Type: application/json'
]);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true); // Mantemos a verificação do SSL para testar o certificado do VPS

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlErrorNo = curl_errno($ch);
$curlErrorMsg = curl_error($ch);
curl_close($ch);

echo "\n--- RESULTADO DA REQUISIÇÃO ---\n";
echo "HTTP Code: {$httpCode}\n";

if ($curlErrorNo !== 0) {
    echo "❌ ERRO cURL ({$curlErrorNo}): {$curlErrorMsg}\n";
    if ($curlErrorNo === 60) {
        echo "\n💡 DICA DE CORREÇÃO (SSL CA CERT): O PHP no VPS não está conseguindo validar o certificado SSL da API. \n";
        echo "Você pode baixar o arquivo cacert.pem atualizado do site do curl (https://curl.se/ca/cacert.pem) e apontar no php.ini em 'curl.cainfo' ou 'openssl.cafile'.\n";
    }
    exit(1);
}

$result = json_decode($response, true);
if (isset($result['organic'])) {
    echo "✅ SUCESSO! A API respondeu perfeitamente.\n";
    echo "Resultados orgânicos encontrados: " . count($result['organic']) . "\n";
} else {
    echo "❌ ERRO na resposta da API do Serper:\n";
    print_r($result);
}
