<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddGpsToEventoContacts extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('evento_contacts', [
            'latitude' => [
                'type' => 'NUMERIC',
                'constraint' => '10,7',
                'null' => true,
            ],
            'longitude' => [
                'type' => 'NUMERIC',
                'constraint' => '11,7',
                'null' => true,
            ],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('evento_contacts', ['latitude', 'longitude']);
    }
}
