<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddRfbVerificationToClientWallets extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('client_wallets', [
            'rfb_situacao_cadastral' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
            ],
            'rfb_verificado_em' => [
                'type' => 'TIMESTAMP',
                'null' => true,
            ],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('client_wallets', 'rfb_situacao_cadastral');
        $this->forge->dropColumn('client_wallets', 'rfb_verificado_em');
    }
}
