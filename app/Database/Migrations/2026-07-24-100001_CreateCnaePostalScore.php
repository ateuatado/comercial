<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Tabela de classificação postal por CNAE.
 *
 * Contém todos os 1.332 CNAEs brasileiros com um score (0–5) indicando
 * o potencial de uso dos serviços postais dos Correios para empresas
 * daquele setor. Usada pelo EnrichProspects para calcular o ranking de
 * prospecção de leads fora de carteira.
 *
 * Gerenciável pelo painel admin — pesos e categorias editáveis manualmente.
 */
class CreateCnaePostalScore extends Migration
{
    public function up(): void
    {
        $this->db->query("
            CREATE TABLE IF NOT EXISTS cnae_postal_score (
                subclasse           VARCHAR(7)   PRIMARY KEY,
                denominacao         TEXT         NOT NULL,
                secao               TEXT,
                divisao             TEXT,
                grupo               TEXT,
                classe              TEXT,

                -- Classificação postal (0 = sem uso, 5 = uso intensivo)
                postal_score        SMALLINT     NOT NULL DEFAULT 0,

                -- Categoria semântica para filtros e relatórios
                -- valores: ecommerce | varejo | industria | distribuicao |
                --          servico | saude | educacao | agro | descarte
                postal_categoria    VARCHAR(30)  DEFAULT 'descarte',

                -- Justificativa auditável da pontuação
                postal_justificativa TEXT,

                -- Controle de revisão manual
                revisado            BOOLEAN      NOT NULL DEFAULT FALSE,
                revisado_em         TIMESTAMP,
                revisado_por        INTEGER,     -- user_id de quem revisou

                created_at          TIMESTAMP    DEFAULT NOW(),
                updated_at          TIMESTAMP    DEFAULT NOW()
            )
        ");

        $this->db->query("
            CREATE INDEX IF NOT EXISTS idx_cnae_postal_score
                ON cnae_postal_score(postal_score DESC)
        ");
        $this->db->query("
            CREATE INDEX IF NOT EXISTS idx_cnae_postal_cat
                ON cnae_postal_score(postal_categoria)
        ");
        $this->db->query("
            CREATE INDEX IF NOT EXISTS idx_cnae_postal_revisado
                ON cnae_postal_score(revisado)
        ");
    }

    public function down(): void
    {
        $this->db->query("DROP TABLE IF EXISTS cnae_postal_score");
    }
}
