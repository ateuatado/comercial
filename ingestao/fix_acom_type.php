<?php

// Script ad-hoc para extrair o tipo_acom da coluna CONTA_NOME do CSV
// e atualizar a tabela vendors.

$csvPath = __DIR__ . '/relarorio_geral_carteiras_clientes.csv';

if (!file_exists($csvPath)) {
    die("Arquivo CSV não encontrado em: $csvPath\n");
}

echo "Conectando ao banco de dados...\n";
$pdo = new PDO('pgsql:host=localhost;port=5432;dbname=spiv', 'postgres', 'LulaTetra26', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
]);

echo "Lendo arquivo CSV...\n";
$fh = fopen($csvPath, 'r');
$header = fgetcsv($fh, 0, ';');
$header = array_map('trim', $header);

$updates = [];
$processed = 0;
$found = 0;

while (($row = fgetcsv($fh, 0, ';')) !== false) {
    if (count($row) < count($header)) continue;
    
    $row = array_map(fn($v) => mb_convert_encoding($v, 'UTF-8', 'ISO-8859-1'), $row);
    $data = array_combine($header, $row);

    $matricula = trim($data['MATRICULA_MCMCU'] ?? '');
    $contaNome = trim($data['CONTA_NOME'] ?? '');

    if ($matricula === '' || $contaNome === '') continue;

    // Procura por ACOM I, ACOM II ou ACOM III no nome da conta.
    // Usamos word boundaries (\b) para não confundir I com inicio de outra palavra, mas 'I', 'II', 'III' costumam ser isolados.
    if (preg_match('/ACOM\s+(III|II|I)\b/i', $contaNome, $matches)) {
        $tipo = strtoupper($matches[1]);
        if (!isset($updates[$matricula])) {
            $updates[$matricula] = $tipo;
            $found++;
        }
    }
    
    $processed++;
}
fclose($fh);

echo "Processadas $processed linhas.\n";
echo "Encontrados $found vendedores com tipo ACOM explícito.\n";

if ($found > 0) {
    echo "Atualizando banco de dados...\n";
    $pdo->beginTransaction();
    $stmt = $pdo->prepare("UPDATE vendors SET tipo_acom = ? WHERE matricula = ?");
    
    $updatedCount = 0;
    foreach ($updates as $matricula => $tipo) {
        $stmt->execute([$tipo, $matricula]);
        if ($stmt->rowCount() > 0) {
            $updatedCount++;
        }
    }
    $pdo->commit();
    echo "Atualizados $updatedCount vendedores no banco de dados com sucesso!\n";
} else {
    echo "Nenhum tipo_acom encontrado para atualizar.\n";
}

echo "Concluído.\n";
