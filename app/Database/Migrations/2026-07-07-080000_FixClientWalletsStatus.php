<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Migration: FixClientWalletsStatus
 *
 * Corrige a migration 100002 que criou o campo status_operacional com:
 *   - DEFAULT 'ativo'  → correto: 'novo'
 *   - CHECK com valores ('ativo','inativo','bloqueado','suspeito')
 *     → correto: ('novo','em_acompanhamento','convertido','sem_contato','bloqueado','inativo')
 *
 * O banco não possui registros reais — a constraint pode ser recriada sem risco.
 */
class FixClientWalletsStatus extends Migration
{
    public function up(): void
    {
        if ($this->db->DBDriver !== 'SQLite3') {
            // 1. Remove constraint antiga
            $this->db->query('
                ALTER TABLE client_wallets
                DROP CONSTRAINT IF EXISTS chk_wallets_status
            ');

            // 2. Atualiza o default da coluna
            $this->db->query("
                ALTER TABLE client_wallets
                ALTER COLUMN status_operacional SET DEFAULT 'novo'
            ");

            // 3. Adiciona constraint com os valores corretos da spec
            $this->db->query("
                ALTER TABLE client_wallets
                ADD CONSTRAINT chk_wallets_status
                CHECK (status_operacional IN (
                    'novo',
                    'em_acompanhamento',
                    'convertido',
                    'sem_contato',
                    'bloqueado',
                    'inativo'
                ))
            ");
        }
    }

    public function down(): void
    {
        if ($this->db->DBDriver !== 'SQLite3') {
            $this->db->query('
                ALTER TABLE client_wallets
                DROP CONSTRAINT IF EXISTS chk_wallets_status
            ');

            $this->db->query("
                ALTER TABLE client_wallets
                ALTER COLUMN status_operacional SET DEFAULT 'ativo'
            ");

            $this->db->query("
                ALTER TABLE client_wallets
                ADD CONSTRAINT chk_wallets_status
                CHECK (status_operacional IN ('ativo','inativo','bloqueado','suspeito'))
            ");
        }
    }
}
