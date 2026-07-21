<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Fase 3.0.3 — Tabela de auditoria de movimentações de vendedores entre coordenadores.
 * Separada de wallet_movements (que rastreia clientes) para manter os conceitos distintos.
 */
class CreateVendorMovementsTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'auto_increment' => true,
            ],
            // Quem foi movimentado
            'matricula' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'comment'    => 'Matrícula do vendedor transferido',
            ],
            // De onde veio
            'coord_origem' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => true,
                'comment'    => 'Matrícula do coordenador de origem',
            ],
            'nome_coord_origem' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => true,
            ],
            // Para onde foi
            'coord_destino' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => true,
                'comment'    => 'Matrícula do coordenador de destino',
            ],
            'nome_coord_destino' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => true,
            ],
            // Contexto organizacional
            'gerencia' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
                'comment'    => 'Gerência onde ocorreu a transferência',
            ],
            'se' => [
                'type'       => 'VARCHAR',
                'constraint' => 10,
                'null'       => true,
            ],
            // Rastreabilidade
            'motivo' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'feito_por' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'comment'    => 'Matrícula de quem executou a transferência',
            ],
            'feito_por_perfil' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'default'    => 'coordenador',
                'comment'    => 'coordenador | admin',
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('matricula');
        $this->forge->addKey('coord_origem');
        $this->forge->addKey('coord_destino');
        $this->forge->addKey('gerencia');
        $this->forge->addKey('feito_por');

        $this->forge->createTable('vendor_movements', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('vendor_movements', true);
    }
}
