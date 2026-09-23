<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateVendedorEventualPortfolioRequests extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'request_id' => ['type' => 'VARCHAR', 'constraint' => 36],
            'opportunity_id' => ['type' => 'INT', 'unsigned' => true],
            'employee_id' => ['type' => 'INT', 'unsigned' => true],
            'cnpj' => ['type' => 'VARCHAR', 'constraint' => 14],
            'reservation_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'status' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'provisional'],
            'requested_at' => ['type' => 'TIMESTAMP'],
            'created_at' => ['type' => 'TIMESTAMP'],
            'updated_at' => ['type' => 'TIMESTAMP'],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('request_id');
        $this->forge->addUniqueKey('opportunity_id');
        $this->forge->addKey(['cnpj', 'status']);
        $this->forge->addKey('reservation_id');
        $this->forge->addForeignKey('opportunity_id', 've_opportunities', 'id', 'RESTRICT', 'CASCADE');
        $this->forge->addForeignKey('employee_id', 'employees', 'id', 'RESTRICT', 'CASCADE');
        $this->forge->createTable('ve_portfolio_requests');

        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'reservation_id' => ['type' => 'VARCHAR', 'constraint' => 36],
            'cnpj' => ['type' => 'VARCHAR', 'constraint' => 14],
            'first_request_id' => ['type' => 'INT', 'unsigned' => true],
            'status' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'active'],
            'created_at' => ['type' => 'TIMESTAMP'],
            'updated_at' => ['type' => 'TIMESTAMP'],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('reservation_id');
        $this->forge->addUniqueKey('cnpj');
        $this->forge->addKey('first_request_id');
        $this->forge->addForeignKey('first_request_id', 've_portfolio_requests', 'id', 'RESTRICT', 'CASCADE');
        $this->forge->createTable('ve_portfolio_reservations');
    }

    public function down(): void
    {
        $this->forge->dropTable('ve_portfolio_reservations', true);
        $this->forge->dropTable('ve_portfolio_requests', true);
    }
}
