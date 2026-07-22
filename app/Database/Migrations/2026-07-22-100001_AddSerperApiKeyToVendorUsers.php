<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Adiciona serper_api_key em vendor_users para chave Serper individual por usuário.
 * A chave é armazenada criptografada (CI4 Encryption) para proteger o dado sensível.
 */
class AddSerperApiKeyToVendorUsers extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('vendor_users', [
            'serper_api_key' => [
                'type'    => 'TEXT',
                'null'    => true,
                'default' => null,
                'comment' => 'Chave Serper.dev do usuário (armazenada criptografada)',
            ],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('vendor_users', 'serper_api_key');
    }
}
