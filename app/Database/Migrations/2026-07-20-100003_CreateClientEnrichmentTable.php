<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateClientEnrichmentTable extends Migration
{
    public function up()
    {
        $this->db->query("
            CREATE TABLE IF NOT EXISTS client_enrichment (
                cnpj                 VARCHAR(14)  NOT NULL PRIMARY KEY,
                website_domain       VARCHAR(255) NULL,
                technologies         JSONB        NULL DEFAULT '[]',
                job_signals          JSONB        NULL DEFAULT '{}',
                logistics_score      SMALLINT     NOT NULL DEFAULT 0,
                score_breakdown      JSONB        NULL DEFAULT '{}',
                score_justification  VARCHAR(500) NULL,
                enriched_at          TIMESTAMP    NULL,
                created_at           TIMESTAMP    NOT NULL DEFAULT NOW(),
                updated_at           TIMESTAMP    NOT NULL DEFAULT NOW()
            )
        ");

        $this->db->query("CREATE INDEX IF NOT EXISTS idx_enrichment_score ON client_enrichment(logistics_score DESC)");
        $this->db->query("CREATE INDEX IF NOT EXISTS idx_enrichment_enriched ON client_enrichment(enriched_at DESC NULLS LAST)");
    }

    public function down()
    {
        $this->db->query("DROP TABLE IF EXISTS client_enrichment");
    }
}
