<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCnaeScoringRulesTable extends Migration
{
    public function up()
    {
        $this->db->query("
            CREATE TABLE IF NOT EXISTS cnae_scoring_rules (
                cnae_code   VARCHAR(10)  NOT NULL PRIMARY KEY,
                weight      SMALLINT     NOT NULL DEFAULT 0 CHECK (weight >= 0 AND weight <= 100),
                description VARCHAR(255) NULL,
                created_at  TIMESTAMP    NOT NULL DEFAULT NOW(),
                updated_at  TIMESTAMP    NOT NULL DEFAULT NOW()
            )
        ");

        $this->db->query("CREATE INDEX IF NOT EXISTS idx_cnae_weight ON cnae_scoring_rules(weight DESC)");
    }

    public function down()
    {
        $this->db->query("DROP TABLE IF EXISTS cnae_scoring_rules");
    }
}
