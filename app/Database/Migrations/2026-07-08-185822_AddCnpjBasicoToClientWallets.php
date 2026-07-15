<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddCnpjBasicoToClientWallets extends Migration
{
    public function up()
    {
        // Adiciona a coluna gerada (STORED para criar índice)
        $this->db->query('ALTER TABLE client_wallets ADD COLUMN cnpj_basico VARCHAR(8) GENERATED ALWAYS AS (SUBSTRING(cnpj, 1, 8)) STORED;');
        
        // Cria um índice na nova coluna para otimizar os JOINs com receita.empresas
        $this->db->query('CREATE INDEX client_wallets_cnpj_basico ON client_wallets (cnpj_basico);');
    }

    public function down()
    {
        $this->db->query('DROP INDEX IF EXISTS client_wallets_cnpj_basico;');
        $this->db->query('ALTER TABLE client_wallets DROP COLUMN IF EXISTS cnpj_basico;');
    }
}
