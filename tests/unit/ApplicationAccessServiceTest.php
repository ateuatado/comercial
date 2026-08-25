<?php

declare(strict_types=1);

use App\Database\Migrations\CreateVendedorEventualFoundation;
use App\Services\AccessAdministrationService;
use App\Services\ApplicationAccessService;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Database;
use Config\VendedorEventual;

require_once APPPATH . 'Database/Migrations/2026-08-25-100001_CreateVendedorEventualFoundation.php';

final class ApplicationAccessServiceTest extends CIUnitTestCase
{
    private BaseConnection $testDb;
    private CreateVendedorEventualFoundation $migration;
    private ApplicationAccessService $service;
    private int $employeeId;
    private int $applicationId;
    private int $campaignId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->testDb = Database::connect('tests');
        $this->migration = new CreateVendedorEventualFoundation(Database::forge('tests'));
        $this->migration->down();
        $this->migration->up();
        $this->service = new ApplicationAccessService();

        /** @var VendedorEventual $config */
        $config = config(VendedorEventual::class);
        $config->enabled = true;

        $this->testDb->table('employees')->insert([
            'employee_id' => 'EMP0001',
            'shield_user_id' => 101,
            'display_name' => 'Empregado Teste',
            'identity_source' => 'demo',
            'employment_status' => 'active',
        ]);
        $this->employeeId = (int) $this->testDb->insertID();

        $application = $this->testDb->table('ve_applications')->where('code', 'vendedor_eventual')->get()->getRowArray();
        $this->applicationId = (int) $application['id'];
        $this->testDb->table('ve_applications')->where('id', $this->applicationId)->update(['enabled' => true]);

        $this->testDb->table('ve_campaigns')->insert([
            'code' => 'PILOTO-01',
            'name' => 'Piloto 01',
            'mode' => 'demonstrative',
            'status' => 'active',
            'starts_at' => '2026-08-01 00:00:00',
            'ends_at' => '2026-09-01 00:00:00',
        ]);
        $this->campaignId = (int) $this->testDb->insertID();
    }

    protected function tearDown(): void
    {
        /** @var VendedorEventual $config */
        $config = config(VendedorEventual::class);
        $config->enabled = false;
        $this->migration->down();
        parent::tearDown();
    }

    public function testMigrationCreatesAndRevertsFoundationTables(): void
    {
        $this->assertTrue($this->testDb->tableExists('employees'));
        $this->assertTrue($this->testDb->tableExists('ve_employee_entitlements'));

        $this->migration->down();

        $this->assertFalse($this->testDb->tableExists('employees'));
        $this->assertFalse($this->testDb->tableExists('ve_employee_entitlements'));
    }

    public function testAdministrativeEntitlementAllowsActiveEmployee(): void
    {
        $this->insertEntitlement('administrator', null, '2026-09-01 00:00:00');

        $this->assertTrue($this->service->hasAccess(
            101,
            'vendedor_eventual',
            'access',
            null,
            new DateTimeImmutable('2026-08-25 12:00:00')
        ));
    }

    public function testDisabledFeatureDeniesOtherwiseValidEntitlement(): void
    {
        $this->insertEntitlement('administrator', null, '2026-09-01 00:00:00');
        config(VendedorEventual::class)->enabled = false;

        $this->assertFalse($this->service->hasAccess(
            101,
            'vendedor_eventual',
            'access',
            null,
            new DateTimeImmutable('2026-08-25 12:00:00')
        ));
    }

    public function testExpiredEntitlementIsDenied(): void
    {
        $this->insertEntitlement('administrator', null, '2026-08-20 00:00:00');

        $this->assertFalse($this->service->hasAccess(
            101,
            'vendedor_eventual',
            'access',
            null,
            new DateTimeImmutable('2026-08-25 12:00:00')
        ));
    }

    public function testClosedCampaignImmediatelyDeniesCampaignEntitlement(): void
    {
        $this->insertEntitlement('campaign', $this->campaignId, '2026-09-01 00:00:00');
        $this->testDb->table('ve_campaigns')->where('id', $this->campaignId)->update(['status' => 'closed']);

        $this->assertFalse($this->service->hasAccess(
            101,
            'vendedor_eventual',
            'access',
            $this->campaignId,
            new DateTimeImmutable('2026-08-25 12:00:00')
        ));
    }

    public function testInactiveEmployeeIsDenied(): void
    {
        $this->insertEntitlement('administrator', null, '2026-09-01 00:00:00');
        $this->testDb->table('employees')->where('id', $this->employeeId)->update(['employment_status' => 'inactive']);

        $this->assertFalse($this->service->hasAccess(
            101,
            'vendedor_eventual',
            'access',
            null,
            new DateTimeImmutable('2026-08-25 12:00:00')
        ));
    }

    public function testCampaignGrantIsCappedAndRevocationIsAudited(): void
    {
        $administration = new AccessAdministrationService();
        $entitlementId = $administration->grant([
            'employee_id' => $this->employeeId,
            'application_id' => $this->applicationId,
            'campaign_id' => $this->campaignId,
            'source' => 'campaign',
            'valid_from' => '2026-08-20 00:00:00',
            'valid_until' => '2026-12-01 00:00:00',
            'reason' => 'Piloto controlado',
        ], 900);

        $entitlement = $this->testDb->table('ve_employee_entitlements')->where('id', $entitlementId)->get()->getRowArray();
        $this->assertSame('2026-09-01 00:00:00', $entitlement['valid_until']);

        $administration->revoke($entitlementId, 901, 'Encerramento antecipado');
        $entitlement = $this->testDb->table('ve_employee_entitlements')->where('id', $entitlementId)->get()->getRowArray();
        $this->assertSame('revoked', $entitlement['status']);
        $this->assertSame(2, $this->testDb->table('ve_access_events')->where('employee_id', $this->employeeId)->countAllResults());
    }

    private function insertEntitlement(string $source, ?int $campaignId, ?string $validUntil): void
    {
        $this->testDb->table('ve_employee_entitlements')->insert([
            'employee_id' => $this->employeeId,
            'application_id' => $this->applicationId,
            'campaign_id' => $campaignId,
            'capability' => 'access',
            'source' => $source,
            'status' => 'active',
            'valid_from' => '2026-08-01 00:00:00',
            'valid_until' => $validUntil,
        ]);
    }
}
