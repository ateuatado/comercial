<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateVendedorEventualCatalogVersions extends Migration
{
    public function up(): void
    {
        $common = [
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'campaign_id' => ['type' => 'INT', 'unsigned' => true],
            'version' => ['type' => 'VARCHAR', 'constraint' => 60],
            'status' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'draft'],
            'created_by' => ['type' => 'INT', 'null' => true],
            'published_at' => ['type' => 'TIMESTAMP', 'null' => true],
            'created_at' => ['type' => 'TIMESTAMP'],
            'updated_at' => ['type' => 'TIMESTAMP'],
        ];

        $this->forge->addField($common + [
            'name' => ['type' => 'VARCHAR', 'constraint' => 150],
            'official_name' => ['type' => 'VARCHAR', 'constraint' => 200, 'null' => true],
            'problem_solved' => ['type' => 'TEXT'],
            'target_profile' => ['type' => 'TEXT'],
            'benefits' => ['type' => 'TEXT'],
            'restrictions' => ['type' => 'TEXT'],
            'requirements' => ['type' => 'TEXT'],
            'documents' => ['type' => 'TEXT'],
            'sales_script' => ['type' => 'TEXT'],
            'faq' => ['type' => 'TEXT'],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['campaign_id', 'version', 'name']);
        $this->forge->addForeignKey('campaign_id', 've_campaigns', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('ve_product_versions');

        $this->forge->addField($common + [
            'title' => ['type' => 'VARCHAR', 'constraint' => 180],
            'questions' => ['type' => 'TEXT'],
            'recommendation_rules' => ['type' => 'TEXT'],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['campaign_id', 'version']);
        $this->forge->addForeignKey('campaign_id', 've_campaigns', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('ve_questionnaire_versions');
    }

    public function down(): void
    {
        $this->forge->dropTable('ve_questionnaire_versions', true);
        $this->forge->dropTable('ve_product_versions', true);
    }
}
