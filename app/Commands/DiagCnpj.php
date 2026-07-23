<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class DiagCnpj extends BaseCommand
{
    protected $group       = 'Diagnostic';
    protected $name        = 'diag:cnpj';
    protected $description = 'Diagnostica CNAEs na Receita';
    protected $usage       = 'diag:cnpj [cnpj]';

    public function run(array $params)
    {
        $cleanCnpj = preg_replace('/[^0-9]/', '', $params[0] ?? '35303077000115');
        $db = \Config\Database::connect();

        $est = $db->query("
            SELECT (cnpj_basico || cnpj_ordem || cnpj_dv) AS cnpj,
                   cnae_fiscal_principal, cnae_fiscal_secundaria
            FROM receita.estabelecimentos
            WHERE (cnpj_basico || cnpj_ordem || cnpj_dv) = ?
            LIMIT 1
        ", [$cleanCnpj])->getRowArray();

        $cnaesList = [];
        if (!empty($est['cnae_fiscal_principal'])) {
            $cnaesList[] = trim($est['cnae_fiscal_principal']);
        }
        if (!empty($est['cnae_fiscal_secundaria'])) {
            foreach (explode(',', $est['cnae_fiscal_secundaria']) as $c) {
                $c = trim($c);
                if (!empty($c) && !in_array($c, $cnaesList)) {
                    $cnaesList[] = $c;
                }
            }
        }

        CLI::write("CNAEs extraídos: " . implode(', ', $cnaesList), 'yellow');

        if (!empty($cnaesList)) {
            $placeholders = implode(',', array_fill(0, count($cnaesList), '?'));
            $descs = $db->query("SELECT codigo, descricao FROM receita.cnaes WHERE codigo IN ({$placeholders})", $cnaesList)->getResultArray();
            print_r($descs);
        }
    }
}
