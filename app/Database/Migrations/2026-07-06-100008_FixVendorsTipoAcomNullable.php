<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Migration: FixVendorsTipoAcomNullable
 *
 * Corrige vendors.tipo_acom para aceitar NULL.
 * Gerentes de Conta não têm tipo_acom (I/II/III) — o campo é exclusivo de ACOMs.
 * Atualiza o check constraint para permitir NULL explicitamente.
 */
class FixVendorsTipoAcomNullable extends Migration
{
    public function up(): void
    {
        if ($this->db->DBDriver !== 'SQLite3') {
            // Permite NULL no campo tipo_acom
            $this->db->query('ALTER TABLE vendors ALTER COLUMN tipo_acom DROP NOT NULL');

            // Recria o check constraint para permitir NULL (Gerente de Conta)
            $this->db->query('ALTER TABLE vendors DROP CONSTRAINT IF EXISTS chk_vendors_tipo_acom');
            $this->db->query("ALTER TABLE vendors ADD CONSTRAINT chk_vendors_tipo_acom CHECK (tipo_acom IS NULL OR tipo_acom IN ('I', 'II', 'III'))");
        }
    }

    public function down(): void
    {
        if ($this->db->DBDriver !== 'SQLite3') {
            // Reverte: NULL → vazio, coluna volta a NOT NULL
            $this->db->query("UPDATE vendors SET tipo_acom = '' WHERE tipo_acom IS NULL");
            $this->db->query('ALTER TABLE vendors ALTER COLUMN tipo_acom SET NOT NULL');
            $this->db->query('ALTER TABLE vendors DROP CONSTRAINT IF EXISTS chk_vendors_tipo_acom');
            $this->db->query("ALTER TABLE vendors ADD CONSTRAINT chk_vendors_tipo_acom CHECK (tipo_acom IN ('I', 'II', 'III'))");
        }
    }
}
