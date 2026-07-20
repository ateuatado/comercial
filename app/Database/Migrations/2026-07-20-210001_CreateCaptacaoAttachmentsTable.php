<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCaptacaoAttachmentsTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'captacao_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'cnpj' => [
                'type'       => 'VARCHAR',
                'constraint' => 14,
            ],
            'matricula' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
            ],
            'filename' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'comment'    => 'Nome gerado no servidor',
            ],
            'original_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'comment'    => 'Nome original do arquivo enviado',
            ],
            'mime_type' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'file_size' => [
                'type'    => 'INT',
                'unsigned'=> true,
                'comment' => 'Tamanho em bytes',
            ],
            'created_at' => [
                'type'    => 'TIMESTAMP',
                'null'    => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('captacao_id');
        $this->forge->addKey('cnpj');
        $this->forge->addForeignKey('captacao_id', 'captacao_requests', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('captacao_attachments');
    }

    public function down(): void
    {
        $this->forge->dropTable('captacao_attachments', true);
    }
}
