<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateVendedorEventualLearningVersions extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'campaign_id' => ['type' => 'INT', 'unsigned' => true],
            'version' => ['type' => 'VARCHAR', 'constraint' => 60],
            'title' => ['type' => 'VARCHAR', 'constraint' => 180],
            'training_content' => ['type' => 'TEXT'],
            'terms_content' => ['type' => 'TEXT'],
            'assessment_question' => ['type' => 'TEXT'],
            'assessment_options' => ['type' => 'TEXT'],
            'correct_option' => ['type' => 'INT', 'unsigned' => true],
            'passing_score' => ['type' => 'INT', 'unsigned' => true, 'default' => 100],
            'status' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'draft'],
            'published_at' => ['type' => 'TIMESTAMP', 'null' => true],
            'created_by' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'created_at' => ['type' => 'TIMESTAMP', 'null' => true],
            'updated_at' => ['type' => 'TIMESTAMP', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['campaign_id', 'version']);
        $this->forge->addKey(['campaign_id', 'status']);
        $this->forge->addForeignKey('campaign_id', 've_campaigns', 'id', 'RESTRICT', 'CASCADE');
        $this->forge->createTable('ve_learning_versions');

        if ($this->db->DBDriver !== 'SQLite3') {
            $this->db->query("ALTER TABLE ve_learning_versions ADD CONSTRAINT chk_ve_learning_status CHECK (status IN ('draft','published','archived'))");
            $this->db->query('ALTER TABLE ve_learning_versions ADD CONSTRAINT chk_ve_learning_score CHECK (passing_score BETWEEN 0 AND 100)');
        }
    }

    public function down(): void
    {
        $this->forge->dropTable('ve_learning_versions', true);
    }
}
