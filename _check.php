<?php
$pdo = new PDO('pgsql:host=localhost;dbname=spiv', 'postgres', 'LulaTetra26');
echo $pdo->query("SELECT count(*) FROM carteira_raw WHERE matricula_mcmcu = '59832'")->fetchColumn();
