<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateVendedorEventualEnrollments extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'campaign_id' => ['type' => 'INT', 'unsigned' => true],
            'employee_id' => ['type' => 'INT', 'unsigned' => true],
            'status' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'started'],
            'terms_version' => ['type' => 'VARCHAR', 'constraint' => 60, 'null' => true],
            'terms_accepted_at' => ['type' => 'TIMESTAMP', 'null' => true],
            'training_version' => ['type' => 'VARCHAR', 'constraint' => 60, 'null' => true],
            'training_completed_at' => ['type' => 'TIMESTAMP', 'null' => true],
            'assessment_score' => ['type' => 'DECIMAL', 'constraint' => '5,2', 'null' => true],
            'assessment_passed' => ['type' => 'BOOLEAN', 'null' => true],
            'qualified_until' => ['type' => 'TIMESTAMP', 'null' => true],
            'enrolled_at' => ['type' => 'TIMESTAMP', 'null' => true],
            'status_reason' => ['type' => 'TEXT', 'null' => true],
            'created_at' => ['type' => 'TIMESTAMP', 'null' => true],
            'updated_at' => ['type' => 'TIMESTAMP', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['campaign_id', 'employee_id']);
        $this->forge->addKey(['employee_id', 'status']);
        $this->forge->addForeignKey('campaign_id', 've_campaigns', 'id', 'RESTRICT', 'CASCADE');
        $this->forge->addForeignKey('employee_id', 'employees', 'id', 'RESTRICT', 'CASCADE');
        $this->forge->createTable('ve_enrollments');

        if ($this->db->DBDriver !== 'SQLite3') {
            $this->db->query("ALTER TABLE ve_enrollments ADD CONSTRAINT chk_ve_enrollment_status CHECK (status IN ('started','in_training','qualified','paused','expired','suspended','closed'))");
            $this->db->query('ALTER TABLE ve_enrollments ADD CONSTRAINT chk_ve_assessment_score CHECK (assessment_score IS NULL OR (assessment_score >= 0 AND assessment_score <= 100))');
            $this->db->query("ALTER TABLE ve_enrollments ADD CONSTRAINT chk_ve_qualified_evidence CHECK (status <> 'qualified' OR (terms_version IS NOT NULL AND terms_accepted_at IS NOT NULL AND training_version IS NOT NULL AND training_completed_at IS NOT NULL AND assessment_passed = TRUE AND enrolled_at IS NOT NULL))");
        }
    }

    public function down(): void
    {
        $this->forge->dropTable('ve_enrollments', true);
    }
}
