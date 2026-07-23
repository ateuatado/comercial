<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class DiagCnpj extends BaseCommand
{
    protected $group       = 'Diagnostic';
    protected $name        = 'diag:cnpj';
    protected $description = 'Diagnostica CNPJ na carteira e localizacao';
    protected $usage       = 'diag:cnpj [cnpj]';

    public function run(array $params)
    {
        $cleanCnpj = preg_replace('/[^0-9]/', '', $params[0] ?? '35303077000115');
        $db = \Config\Database::connect();

        CLI::write("=== BUSCANDO CNPJ: {$cleanCnpj} ===", 'yellow');

        $rows = $db->query("SELECT id, cnpj, matricula_mcmcu, forca_vendas_nome, gerencia FROM carteira_raw WHERE regexp_replace(cnpj, '[^0-9]', '', 'g') = ?", [$cleanCnpj])->getResultArray();
        CLI::write("CARTEIRA RAW (" . count($rows) . " resultados):", 'green');
        print_r($rows);

        $locs = $db->query("SELECT * FROM client_locations WHERE cnpj = ?", [$cleanCnpj])->getResultArray();
        CLI::write("CLIENT LOCATIONS (" . count($locs) . " resultados):", 'green');
        print_r($locs);
    }
}
