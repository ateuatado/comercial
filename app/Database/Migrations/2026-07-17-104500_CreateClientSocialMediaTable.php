<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateClientSocialMediaTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'auto_increment' => true,
            ],
            'cnpj' => [
                'type'       => 'VARCHAR',
                'constraint' => 14,
            ],
            'network' => [
                'type'       => 'VARCHAR',
                'constraint' => 50, // instagram, linkedin, facebook, website
            ],
            'url' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'status' => [
                'type'       => 'VARCHAR',
                'constraint' => 30, // sugestao, validado, rejeitado
                'default'    => 'sugestao',
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['cnpj', 'network', 'url']);
        $this->forge->createTable('client_social_media', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('client_social_media', true);
    }
}
