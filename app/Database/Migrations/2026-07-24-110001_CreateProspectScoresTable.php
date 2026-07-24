<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Migration: CreateProspectScoresTable
 * Tabela de cache para o Ranking de Prospects Fora de Carteira.
 * Armazena a pontuação calculada tripartida (Score CNAE, Idade/Mortalidade e Adequação de Capital Social).
 */
class CreateProspectScoresTable extends Migration
{
    public function up(): void
    {
        $this->db->query("
            CREATE TABLE IF NOT EXISTS prospect_scores (
                cnpj                    VARCHAR(14)    PRIMARY KEY,
                razao_social            TEXT           NOT NULL,
                cnae_fiscal_principal   VARCHAR(7),
                postal_categoria        VARCHAR(30)    DEFAULT 'servico',
                
                -- Scores de 0 a 100
                score_cnae              SMALLINT       NOT NULL DEFAULT 0,
                score_idade             SMALLINT       NOT NULL DEFAULT 0,
                score_capital           SMALLINT       NOT NULL DEFAULT 0,
                score_email             SMALLINT       NOT NULL DEFAULT 0,
                fator_setor             NUMERIC(3,2)   NOT NULL DEFAULT 1.00,
                
                -- Score Final Combinado (0 a 120+)
                score_final             NUMERIC(5,2)   NOT NULL DEFAULT 0.00,
                
                -- Metadados de Auditoria / Estatística
                dt_abertura             DATE,
                idade_anos              SMALLINT,
                capital_social          NUMERIC(15,2)  DEFAULT 0.00,
                mediana_setor           NUMERIC(15,2)  DEFAULT 0.00,
                razao_capital           NUMERIC(12,2)  DEFAULT 1.00,
                
                uf                      VARCHAR(2),
                municipio               VARCHAR(7),
                
                calculated_at           TIMESTAMP      DEFAULT NOW()
            )
        ");

        $this->db->query("
            CREATE INDEX IF NOT EXISTS idx_prospect_scores_final 
                ON prospect_scores(score_final DESC)
        ");

        $this->db->query("
            CREATE INDEX IF NOT EXISTS idx_prospect_scores_cat 
                ON prospect_scores(postal_categoria)
        ");

        $this->db->query("
            CREATE INDEX IF NOT EXISTS idx_prospect_scores_uf 
                ON prospect_scores(uf)
        ");
    }

    public function down(): void
    {
        $this->db->query("DROP TABLE IF EXISTS prospect_scores");
    }
}
