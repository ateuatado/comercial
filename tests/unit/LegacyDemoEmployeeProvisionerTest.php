<?php

declare(strict_types=1);

use App\Services\LegacyDemoEmployeeProvisioner;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Database\Forge;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Database;

final class LegacyDemoEmployeeProvisionerTest extends CIUnitTestCase
{
    private BaseConnection $testDb;
    private Forge $forge;

    protected function setUp(): void
    {
        parent::setUp();

        $this->testDb = Database::connect('tests');
        $this->forge = Database::forge('tests');
        $this->dropTables();
        $this->createTables();
    }

    protected function tearDown(): void
    {
        $this->dropTables();
        parent::tearDown();
    }

    public function testClosedListContainsOnlyExpectedPersonas(): void
    {
        $ids = LegacyDemoEmployeeProvisioner::employeeIds();

        $this->assertCount(60, $ids);
        $this->assertSame(['A0001', 'C0101', 'C0102', 'C0103'], array_slice($ids, 0, 4));
        $this->assertSame('V0101', $ids[4]);
        $this->assertSame('V0156', $ids[59]);
        $this->assertNotContains('EMP0001', $ids);
    }

    public function testSyncRegistersExistingShieldUsersWithoutCreatingUnlistedIdentity(): void
    {
        $this->insertUsers(['A0001', 'C0101', 'V0101', 'X9999']);
        $this->testDb->table('vendor_users')->insertBatch([
            ['matricula' => 'C0101', 'nome' => 'Coordenação 01', 'gerencia' => 'GERÊNCIA 01'],
            ['matricula' => 'V0101', 'nome' => 'Vendedor 01', 'gerencia' => 'GERÊNCIA 01'],
        ]);

        $service = new LegacyDemoEmployeeProvisioner($this->testDb);
        $first = $service->syncExistingShieldUsers();

        $this->assertSame(3, $first['created']);
        $this->assertSame(57, $first['skipped']);
        $this->assertSame(0, $first['conflicts']);
        $this->assertSame(3, $this->testDb->table('employees')->countAllResults());
        $this->assertNull($this->testDb->table('employees')->where('employee_id', 'X9999')->get()->getRowArray());

        $vendor = $this->testDb->table('employees')->where('employee_id', 'V0101')->get()->getRowArray();
        $this->assertSame('Vendedor 01', $vendor['display_name']);
        $this->assertSame('GERÊNCIA 01', $vendor['organizational_unit']);
        $this->assertSame('demo', $vendor['identity_source']);
        $this->assertSame('active', $vendor['employment_status']);

        $second = $service->syncExistingShieldUsers();
        $this->assertSame(0, $second['created']);
        $this->assertSame(3, $second['updated']);
        $this->assertSame(3, $this->testDb->table('employees')->countAllResults());
    }

    public function testSyncDoesNotOverwriteNonDemoEmployee(): void
    {
        $this->insertUsers(['V0101']);
        $this->testDb->table('employees')->insert([
            'employee_id' => 'V0101',
            'shield_user_id' => null,
            'display_name' => 'Identidade corporativa',
            'identity_source' => 'ldap',
            'employment_status' => 'active',
        ]);

        $result = (new LegacyDemoEmployeeProvisioner($this->testDb))->syncExistingShieldUsers();
        $employee = $this->testDb->table('employees')->where('employee_id', 'V0101')->get()->getRowArray();

        $this->assertSame(1, $result['conflicts']);
        $this->assertSame('ldap', $employee['identity_source']);
        $this->assertNull($employee['shield_user_id']);
    }

    /** @param list<string> $usernames */
    private function insertUsers(array $usernames): void
    {
        foreach ($usernames as $username) {
            $this->testDb->table('users')->insert(['username' => $username]);
        }
    }

    private function createTables(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'INTEGER', 'auto_increment' => true],
            'username' => ['type' => 'VARCHAR', 'constraint' => 30],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('username');
        $this->forge->createTable('users');

        $this->forge->addField([
            'id' => ['type' => 'INTEGER', 'auto_increment' => true],
            'matricula' => ['type' => 'VARCHAR', 'constraint' => 20],
            'nome' => ['type' => 'VARCHAR', 'constraint' => 200],
            'gerencia' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('matricula');
        $this->forge->createTable('vendor_users');

        $this->forge->addField([
            'id' => ['type' => 'INTEGER', 'auto_increment' => true],
            'employee_id' => ['type' => 'VARCHAR', 'constraint' => 20],
            'shield_user_id' => ['type' => 'INTEGER', 'null' => true],
            'display_name' => ['type' => 'VARCHAR', 'constraint' => 200],
            'organizational_unit' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'identity_source' => ['type' => 'VARCHAR', 'constraint' => 20],
            'employment_status' => ['type' => 'VARCHAR', 'constraint' => 20],
            'last_synced_at' => ['type' => 'DATETIME', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('employee_id');
        $this->forge->addUniqueKey('shield_user_id');
        $this->forge->createTable('employees');
    }

    private function dropTables(): void
    {
        $this->forge->dropTable('employees', true);
        $this->forge->dropTable('vendor_users', true);
        $this->forge->dropTable('users', true);
    }
}
