<?php
$pdo = new PDO('pgsql:host=localhost;dbname=spiv', 'postgres', 'LulaTetra26');
$stmt = $pdo->query('SELECT * FROM carteira_raw LIMIT 1');
print_r(array_keys($stmt->fetch(PDO::FETCH_ASSOC) ?: []));
