<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Amplia client_wallets.origem_atribuicao de VARCHAR(10) para VARCHAR(30)
 * para comportar valores como 'coordenador', 'importacao', 'distribuicao', etc.
 */
class AlterClientWalletsOrigemAtribuicao extends Migration
{
    public function up(): void
    {
        $this->db->query(
            "ALTER TABLE client_wallets ALTER COLUMN origem_atribuicao TYPE VARCHAR(30)"
        );
    }

    public function down(): void
    {
        $this->db->query(
            "ALTER TABLE client_wallets ALTER COLUMN origem_atribuicao TYPE VARCHAR(10)"
        );
    }
}
