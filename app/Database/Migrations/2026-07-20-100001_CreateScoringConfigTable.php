<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateScoringConfigTable extends Migration
{
    public function up()
    {
        $this->db->query("
            CREATE TABLE IF NOT EXISTS scoring_config (
                key         VARCHAR(50)  NOT NULL PRIMARY KEY,
                value       VARCHAR(255) NOT NULL,
                label       VARCHAR(255) NULL,
                created_at  TIMESTAMP    NOT NULL DEFAULT NOW(),
                updated_at  TIMESTAMP    NOT NULL DEFAULT NOW()
            )
        ");
    }

    public function down()
    {
        $this->db->query("DROP TABLE IF EXISTS scoring_config");
    }
}
