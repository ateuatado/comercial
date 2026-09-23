<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateVendedorEventualFoundation extends Migration
{
    public function up(): void
    {
        $this->createEmployees();
        $this->createApplications();
        $this->createCampaigns();
        $this->createEntitlements();
        $this->createAccessEvents();
        $this->addConstraints();
    }

    public function down(): void
    {
        $this->forge->dropTable('ve_access_events', true);
        $this->forge->dropTable('ve_employee_entitlements', true);
        $this->forge->dropTable('ve_campaigns', true);
        $this->forge->dropTable('ve_applications', true);
        $this->forge->dropTable('employees', true);
    }

    private function createEmployees(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'employee_id' => ['type' => 'VARCHAR', 'constraint' => 20],
            'shield_user_id' => ['type' => 'INT', 'null' => true],
            'display_name' => ['type' => 'VARCHAR', 'constraint' => 200],
            'organizational_unit' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'identity_source' => ['type' => 'VARCHAR', 'constraint' => 20],
            'employment_status' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'active'],
            'last_synced_at' => ['type' => 'TIMESTAMP', 'null' => true],
            'created_at' => ['type' => 'TIMESTAMP', 'null' => true],
            'updated_at' => ['type' => 'TIMESTAMP', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('employee_id');
        $this->forge->addUniqueKey('shield_user_id');
        $this->forge->addKey(['identity_source', 'employment_status']);
        $this->forge->createTable('employees');
    }

    private function createApplications(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'code' => ['type' => 'VARCHAR', 'constraint' => 60],
            'name' => ['type' => 'VARCHAR', 'constraint' => 120],
            'description' => ['type' => 'TEXT', 'null' => true],
            'enabled' => ['type' => 'BOOLEAN', 'default' => false],
            'created_at' => ['type' => 'TIMESTAMP', 'null' => true],
            'updated_at' => ['type' => 'TIMESTAMP', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('code');
        $this->forge->createTable('ve_applications');

        $this->db->table('ve_applications')->insert([
            'code' => 'vendedor_eventual',
            'name' => 'Vendedor Eventual',
            'description' => 'Participação voluntária em campanhas comerciais demonstrativas.',
            'enabled' => false,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    private function createCampaigns(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'code' => ['type' => 'VARCHAR', 'constraint' => 60],
            'name' => ['type' => 'VARCHAR', 'constraint' => 150],
            'mode' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'demonstrative'],
            'status' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'draft'],
            'starts_at' => ['type' => 'TIMESTAMP', 'null' => true],
            'ends_at' => ['type' => 'TIMESTAMP', 'null' => true],
            'created_by' => ['type' => 'INT', 'null' => true],
            'created_at' => ['type' => 'TIMESTAMP', 'null' => true],
            'updated_at' => ['type' => 'TIMESTAMP', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('code');
        $this->forge->addKey(['status', 'starts_at', 'ends_at']);
        $this->forge->createTable('ve_campaigns');
    }

    private function createEntitlements(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'employee_id' => ['type' => 'INT', 'unsigned' => true],
            'application_id' => ['type' => 'INT', 'unsigned' => true],
            'campaign_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'capability' => ['type' => 'VARCHAR', 'constraint' => 100, 'default' => 'access'],
            'source' => ['type' => 'VARCHAR', 'constraint' => 20],
            'status' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'active'],
            'valid_from' => ['type' => 'TIMESTAMP'],
            'valid_until' => ['type' => 'TIMESTAMP', 'null' => true],
            'granted_by' => ['type' => 'INT', 'null' => true],
            'reason' => ['type' => 'TEXT', 'null' => true],
            'revoked_at' => ['type' => 'TIMESTAMP', 'null' => true],
            'revoked_by' => ['type' => 'INT', 'null' => true],
            'created_at' => ['type' => 'TIMESTAMP', 'null' => true],
            'updated_at' => ['type' => 'TIMESTAMP', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['employee_id', 'application_id', 'capability']);
        $this->forge->addKey(['campaign_id', 'status']);
        $this->forge->addForeignKey('employee_id', 'employees', 'id', 'RESTRICT', 'CASCADE');
        $this->forge->addForeignKey('application_id', 've_applications', 'id', 'RESTRICT', 'CASCADE');
        $this->forge->addForeignKey('campaign_id', 've_campaigns', 'id', 'RESTRICT', 'CASCADE');
        $this->forge->createTable('ve_employee_entitlements');
    }

    private function createAccessEvents(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'employee_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'application_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'campaign_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'event_type' => ['type' => 'VARCHAR', 'constraint' => 50],
            'actor_user_id' => ['type' => 'INT', 'null' => true],
            'metadata' => ['type' => 'TEXT', 'null' => true],
            'created_at' => ['type' => 'TIMESTAMP'],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['employee_id', 'created_at']);
        $this->forge->addKey(['application_id', 'campaign_id']);
        $this->forge->addForeignKey('employee_id', 'employees', 'id', 'RESTRICT', 'SET NULL');
        $this->forge->addForeignKey('application_id', 've_applications', 'id', 'RESTRICT', 'SET NULL');
        $this->forge->addForeignKey('campaign_id', 've_campaigns', 'id', 'RESTRICT', 'SET NULL');
        $this->forge->createTable('ve_access_events');
    }

    private function addConstraints(): void
    {
        if ($this->db->DBDriver === 'SQLite3') {
            return;
        }

        $this->db->query("ALTER TABLE employees ADD CONSTRAINT chk_employees_source CHECK (identity_source IN ('demo','ldap'))");
        $this->db->query("ALTER TABLE employees ADD CONSTRAINT chk_employees_status CHECK (employment_status IN ('active','inactive','suspended','terminated'))");
        $this->db->query("ALTER TABLE ve_campaigns ADD CONSTRAINT chk_ve_campaign_mode CHECK (mode IN ('demonstrative'))");
        $this->db->query("ALTER TABLE ve_campaigns ADD CONSTRAINT chk_ve_campaign_status CHECK (status IN ('draft','published','active','suspended','closed','archived'))");
        $this->db->query("ALTER TABLE ve_campaigns ADD CONSTRAINT chk_ve_campaign_period CHECK (ends_at IS NULL OR starts_at IS NULL OR ends_at > starts_at)");
        $this->db->query("ALTER TABLE ve_employee_entitlements ADD CONSTRAINT chk_ve_entitlement_source CHECK (source IN ('administrator','campaign','system'))");
        $this->db->query("ALTER TABLE ve_employee_entitlements ADD CONSTRAINT chk_ve_entitlement_status CHECK (status IN ('active','suspended','revoked'))");
        $this->db->query("ALTER TABLE ve_employee_entitlements ADD CONSTRAINT chk_ve_entitlement_period CHECK (valid_until IS NULL OR valid_until > valid_from)");
        $this->db->query("ALTER TABLE ve_employee_entitlements ADD CONSTRAINT chk_ve_campaign_entitlement CHECK (source <> 'campaign' OR (campaign_id IS NOT NULL AND valid_until IS NOT NULL))");
    }
}
