<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddContratoProdutosToEventoContacts extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('evento_contacts', [
            'possui_contrato' => [
                'type'    => 'BOOLEAN',
                'default' => false,
            ],
            'produtos_interesse' => [
                'type' => 'TEXT',
                'null' => true,
            ],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('evento_contacts', ['possui_contrato', 'produtos_interesse']);
    }
}
