<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateVendorUsersTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'auto_increment' => true,
            ],
            'matricula' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
            ],
            'nome' => [
                'type'       => 'VARCHAR',
                'constraint' => 200,
            ],
            'email' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'perfil_vendedor' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
            ],
            'se' => [
                'type'       => 'VARCHAR',
                'constraint' => 10,
                'null'       => true,
            ],
            'gerencia' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
            'mtr_coordenador' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => true,
            ],
            'nome_coordenador' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => true,
            ],
            'gerencia_vendas' => [
                'type'       => 'VARCHAR',
                'constraint' => 200,
                'null'       => true,
            ],
            'shield_user_id' => [
                'type' => 'INT',
                'null' => true,
            ],
            'ativo' => [
                'type'    => 'BOOLEAN',
                'default' => true,
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
        $this->forge->addUniqueKey('matricula');
        $this->forge->addKey('mtr_coordenador');
        $this->forge->addKey('se');
        $this->forge->addKey('perfil_vendedor');
        $this->forge->addKey('shield_user_id');
        $this->forge->addKey('ativo');

        $this->forge->createTable('vendor_users', true);
    }

    public function down()
    {
        $this->forge->dropTable('vendor_users', true);
    }
}
