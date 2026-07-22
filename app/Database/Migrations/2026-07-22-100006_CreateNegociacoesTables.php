<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateNegociacoesTables extends Migration
{
    public function up(): void
    {
        // ── plano_de_vendas ─────────────────────────────────────────────────
        $this->db->query('
            CREATE TABLE IF NOT EXISTS plano_de_vendas (
                id               SERIAL PRIMARY KEY,
                hashtag          VARCHAR(60)  NOT NULL UNIQUE,
                ano              INTEGER      NOT NULL,
                nome_da_acao     VARCHAR(60)  NOT NULL,
                detalhe_da_acao  TEXT,
                objetivo_da_acao TEXT,
                created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ');

        // ── negociacoes ──────────────────────────────────────────────────────
        $this->db->query('
            CREATE TABLE IF NOT EXISTS negociacoes (
                id                  SERIAL PRIMARY KEY,
                negociacao_id       INTEGER UNIQUE,
                grupo_cliente       TEXT,
                hashtag             VARCHAR(60),
                descricao           TEXT,
                status              VARCHAR(30),
                resultado           VARCHAR(50),
                rec_prevista        NUMERIC(14,2),
                rec_realizada       NUMERIC(14,2),
                neg_valida          SMALLINT,
                forca_de_vendas     VARCHAR(200),
                detalhe_neg_valida  TEXT,
                data_cadastro       DATE,
                inicio_previsto     VARCHAR(40),
                ultimo_realizado    VARCHAR(40),
                tipo                VARCHAR(20),
                segmento            VARCHAR(30),
                created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ');

        // Índices úteis para o relatório
        $this->db->query('CREATE INDEX IF NOT EXISTS idx_neg_hashtag  ON negociacoes(hashtag)');
        $this->db->query('CREATE INDEX IF NOT EXISTS idx_neg_status   ON negociacoes(status)');
        $this->db->query('CREATE INDEX IF NOT EXISTS idx_neg_tipo     ON negociacoes(tipo)');
        $this->db->query('CREATE INDEX IF NOT EXISTS idx_neg_segmento ON negociacoes(segmento)');
    }


    public function down(): void
    {
        $this->forge->dropTable('negociacoes',    true);
        $this->forge->dropTable('plano_de_vendas', true);
    }
}
