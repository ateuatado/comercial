<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Fase 3.0.4 — Índice em vendor_users.gerencia para otimizar as guards de escopo
 * do coordenador, que filtram todos os recursos pela gerência do logado.
 */
class AddGerenciaIndexToVendorUsers extends Migration
{
    public function up(): void
    {
        // PostgreSQL: CREATE INDEX IF NOT EXISTS
        $this->db->query('CREATE INDEX IF NOT EXISTS idx_vendor_users_gerencia ON vendor_users (gerencia)');
    }

    public function down(): void
    {
        $this->db->query('DROP INDEX IF EXISTS idx_vendor_users_gerencia');
    }
}
