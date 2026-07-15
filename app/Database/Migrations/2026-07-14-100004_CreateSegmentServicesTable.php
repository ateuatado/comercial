<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSegmentServicesTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'auto_increment' => true,
            ],
            'segmento_mercado' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
            ],
            'servico_nome' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'servico_descricao' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'icone' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
            ],
            'cor' => [
                'type'       => 'VARCHAR',
                'constraint' => 7,
                'null'       => true,
            ],
            'ordem' => [
                'type'    => 'INT',
                'default' => 0,
            ],
            'ativo' => [
                'type'    => 'BOOLEAN',
                'default' => true,
            ],
        ]);

        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('segmento_mercado');
        $this->forge->addKey('ativo');

        $this->forge->createTable('segment_services', true);
    }

    public function down()
    {
        $this->forge->dropTable('segment_services', true);
    }
}
