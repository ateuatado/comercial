<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Migration: CreateProspectingReviewsTable
 *
 * Aprovações, revisões e liberações de suspeitas de prospecção.
 * Toda decisão tomada por admin ou supervisor sobre uma suspeita
 * fica registrada aqui com justificativa obrigatória.
 *
 * Dependência: prospecting_flags (migration 100005) deve existir.
 */
class CreateProspectingReviewsTable extends Migration
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
            // Suspeita a que esta revisão se refere
            'flag_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => false,
            ],
            // user_id do admin ou supervisor que tomou a decisão
            'revisado_por' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => false,
            ],
            // liberado = cliente pode entrar na carteira; rejeitado = mantém suspeita
            'decisao' => [
                'type'       => 'VARCHAR',
                'constraint' => 10,
                'null'       => false,
            ],
            'justificativa' => [
                'type' => 'TEXT',
                'null' => false,
            ],
            'created_at' => [
                'type' => 'TIMESTAMP',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('flag_id');
        $this->forge->addForeignKey('flag_id', 'prospecting_flags', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('prospecting_reviews');

        if ($this->db->DBDriver !== 'SQLite3') {
            $this->db->query("ALTER TABLE prospecting_reviews ADD CONSTRAINT chk_reviews_decisao CHECK (decisao IN ('liberado','rejeitado'))");
        }
    }

    public function down(): void
    {
        $this->forge->dropTable('prospecting_reviews', true);
    }
}
