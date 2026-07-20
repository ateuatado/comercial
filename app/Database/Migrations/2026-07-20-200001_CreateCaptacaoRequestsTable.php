<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Migration: CreateCaptacaoRequestsTable
 *
 * Tabela de Pedidos de Captação (PR-CAP).
 * Um vendedor solicita a adição de um CNPJ à sua carteira.
 * Um admin ou coordenador aprova, rejeita ou pede mais informações.
 *
 * Regra de negócio:
 *  - Um mesmo CNPJ não pode estar ativo em duas carteiras simultaneamente.
 *  - Se aprovado, o sistema TRANSFERE o CNPJ (remove do vendedor anterior, se houver).
 *  - Não há limite de PR-CAPs por vendedor.
 */
class CreateCaptacaoRequestsTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            // ─── Identificação ────────────────────────────────────
            'cnpj' => [
                'type'       => 'CHAR',
                'constraint' => 14,
                'null'       => false,
            ],
            'matricula' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => false,
                'comment'    => 'Matrícula do vendedor solicitante',
            ],
            // ─── Declaração do vendedor ───────────────────────────
            'justificativa' => [
                'type' => 'TEXT',
                'null' => false,
                'comment' => 'Obrigatória — por que o vendedor quer este cliente',
            ],
            'tempo_contato' => [
                'type'       => 'VARCHAR',
                'constraint' => 300,
                'null'       => true,
                'comment'    => 'Declaração livre: há quanto tempo está em negociação',
            ],
            'canais_contato' => [
                'type'    => 'TEXT',
                'null'    => true,
                'comment' => 'JSON: ["telefone","email","visita","whatsapp"]',
            ],
            'referencia_doc' => [
                'type'    => 'TEXT',
                'null'    => true,
                'comment' => 'Referência textual a documento externo (proposta, e-mail, etc.)',
            ],
            // ─── Fluxo administrativo ────────────────────────────
            'status' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'default'    => 'pendente',
                'comment'    => 'pendente | aprovado | rejeitado | mais_info',
            ],
            'admin_obs' => [
                'type' => 'TEXT',
                'null' => true,
                'comment' => 'Observação/motivo da decisão pelo admin',
            ],
            'respondido_por' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
                'comment'    => 'Username ou matrícula do admin/coordenador que decidiu',
            ],
            // ─── Contexto no momento do pedido ───────────────────
            'cnpj_em_outra_carteira' => [
                'type'    => 'BOOLEAN',
                'default' => false,
                'comment' => 'True se o CNPJ pertencia a outro vendedor no momento do pedido',
            ],
            'carteira_anterior' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => true,
                'comment'    => 'Matrícula do vendedor anterior (para caso de transferência)',
            ],
            // ─── Timestamps ──────────────────────────────────────
            'created_at' => [
                'type' => 'TIMESTAMP',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'TIMESTAMP',
                'null' => true,
            ],
            'decided_at' => [
                'type' => 'TIMESTAMP',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->createTable('captacao_requests');

        $db = db_connect();
        $prefix = $this->db->prefixTable('captacao_requests');

        $db->query("CREATE INDEX idx_captacao_cnpj       ON {$prefix} (cnpj)");
        $db->query("CREATE INDEX idx_captacao_matricula  ON {$prefix} (matricula)");
        $db->query("CREATE INDEX idx_captacao_status     ON {$prefix} (status)");
        $db->query("CREATE INDEX idx_captacao_created    ON {$prefix} (created_at DESC)");
    }

    public function down(): void
    {
        $this->forge->dropTable('captacao_requests', true);
    }
}
