<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Fase 3.0.2 — Adiciona rastreabilidade de perfil em wallet_movements.
 * Permite saber se a ação foi feita por admin, coordenador ou sistema.
 */
class AddRealizadoPorPerfilToWalletMovements extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('wallet_movements', [
            'realizado_por_perfil' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => true,
                'after'      => 'atribuido_por',
                'comment'    => 'admin | coordenador | sistema',
            ],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('wallet_movements', 'realizado_por_perfil');
    }
}
