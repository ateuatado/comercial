<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Migration: CreateProspectingFlagsTable
 *
 * Suspeitas de prospecção antifraude.
 * Cada registro representa um alerta baseado em CPF de sócio
 * associado a CNPJs com histórico problemático.
 *
 * Evidências mínimas conforme spec: cpf_socio, cnpj_relacionado,
 * motivo, analisado_em, analisado_por e complemento.
 */
class CreateProspectingFlagsTable extends Migration
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
            // CNPJ que está sendo prospectado / suspeito
            'cnpj' => [
                'type'       => 'VARCHAR',
                'constraint' => 14,
                'null'       => false,
            ],
            // CPF do sócio que disparou o alerta (11 dígitos sem formatação)
            'cpf_socio' => [
                'type'       => 'VARCHAR',
                'constraint' => 11,
                'null'       => false,
            ],
            // CNPJ que originou o histórico problemático
            'cnpj_relacionado' => [
                'type'       => 'VARCHAR',
                'constraint' => 14,
                'null'       => true,
            ],
            'motivo' => [
                'type' => 'TEXT',
                'null' => false,
            ],
            // user_id do analista responsável pelo registro
            'analisado_por' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => true,
            ],
            'analisado_em' => [
                'type' => 'TIMESTAMP',
                'null' => true,
            ],
            // Valores: pendente, liberado, rejeitado
            'status' => [
                'type'       => 'VARCHAR',
                'constraint' => 10,
                'null'       => false,
                'default'    => 'pendente',
            ],
            'complemento' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'TIMESTAMP',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'TIMESTAMP',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('cnpj');
        $this->forge->addKey('cpf_socio');
        $this->forge->createTable('prospecting_flags');

        $this->db->query("ALTER TABLE prospecting_flags ADD CONSTRAINT chk_flags_status CHECK (status IN ('pendente','liberado','rejeitado'))");
    }

    public function down(): void
    {
        $this->forge->dropTable('prospecting_flags', true);
    }
}
