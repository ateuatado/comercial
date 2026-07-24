<?php

$filePath = 'app/Controllers/VendedorController.php';
$content = file_get_contents($filePath);

// Clean up corrupted line around rankingApi
$content = preg_replace('/\/\/ [^\n]*public function rankingApi\(\)/u', "    public function rankingApi()", $content);

file_put_contents($filePath, $content);
echo "Fixed VendedorController.php successfully!\n";
