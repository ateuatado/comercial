<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Adiciona campo de visibilidade às anotações dos vendedores.
 *
 * publica = false (padrão) → nota privada, apenas o próprio vendedor vê
 * publica = true           → nota pública, visível a todos os usuários do sistema
 *
 * Padrão seguro: notas existentes ficam privadas — nenhuma informação vaza.
 */
class AddPublicaToVendorNotes extends Migration
{
    public function up(): void
    {
        $this->db->query(
            "ALTER TABLE vendor_notes ADD COLUMN publica BOOLEAN NOT NULL DEFAULT false"
        );
    }

    public function down(): void
    {
        $this->db->query(
            "ALTER TABLE vendor_notes DROP COLUMN publica"
        );
    }
}
