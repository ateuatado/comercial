<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateVendedorEventualOpportunities extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'correlation_id' => ['type' => 'VARCHAR', 'constraint' => 36],
            'campaign_id' => ['type' => 'INT', 'unsigned' => true],
            'originator_employee_id' => ['type' => 'INT', 'unsigned' => true],
            'current_conductor_employee_id' => ['type' => 'INT', 'unsigned' => true],
            'questionnaire_version_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'cnpj' => ['type' => 'VARCHAR', 'constraint' => 14],
            'contact_context' => ['type' => 'TEXT'],
            'channel' => ['type' => 'VARCHAR', 'constraint' => 30],
            'status' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'registered'],
            'contacted_at' => ['type' => 'TIMESTAMP'],
            'created_at' => ['type' => 'TIMESTAMP'],
            'updated_at' => ['type' => 'TIMESTAMP'],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('correlation_id');
        $this->forge->addKey(['originator_employee_id', 'created_at']);
        $this->forge->addKey(['cnpj', 'campaign_id']);
        $this->forge->addForeignKey('campaign_id', 've_campaigns', 'id', 'RESTRICT', 'CASCADE');
        $this->forge->addForeignKey('originator_employee_id', 'employees', 'id', 'RESTRICT', 'CASCADE');
        $this->forge->addForeignKey('current_conductor_employee_id', 'employees', 'id', 'RESTRICT', 'CASCADE');
        $this->forge->addForeignKey('questionnaire_version_id', 've_questionnaire_versions', 'id', 'RESTRICT', 'SET NULL');
        $this->forge->createTable('ve_opportunities');

        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'opportunity_id' => ['type' => 'INT', 'unsigned' => true],
            'event_id' => ['type' => 'VARCHAR', 'constraint' => 36],
            'event_type' => ['type' => 'VARCHAR', 'constraint' => 50],
            'actor_employee_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'actor_user_id' => ['type' => 'INT', 'null' => true],
            'channel' => ['type' => 'VARCHAR', 'constraint' => 30],
            'occurred_at' => ['type' => 'TIMESTAMP'],
            'received_at' => ['type' => 'TIMESTAMP'],
            'content_version' => ['type' => 'VARCHAR', 'constraint' => 60, 'null' => true],
            'metadata' => ['type' => 'TEXT', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('event_id');
        $this->forge->addKey(['opportunity_id', 'occurred_at']);
        $this->forge->addForeignKey('opportunity_id', 've_opportunities', 'id', 'RESTRICT', 'CASCADE');
        $this->forge->addForeignKey('actor_employee_id', 'employees', 'id', 'RESTRICT', 'SET NULL');
        $this->forge->createTable('ve_opportunity_events');
    }

    public function down(): void
    {
        $this->forge->dropTable('ve_opportunity_events', true);
        $this->forge->dropTable('ve_opportunities', true);
    }
}
