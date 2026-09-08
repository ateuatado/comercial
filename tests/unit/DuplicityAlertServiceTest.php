<?php

declare(strict_types=1);

use App\Database\Migrations\CreateVendedorEventualCatalogVersions;
use App\Database\Migrations\CreateVendedorEventualEnrollments;
use App\Database\Migrations\CreateVendedorEventualFoundation;
use App\Database\Migrations\CreateVendedorEventualOpportunities;
use App\Database\Migrations\CreateVendedorEventualPortfolioRequests;
use App\Services\DuplicityAlertService;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Database;

require_once APPPATH . 'Database/Migrations/2026-08-25-100001_CreateVendedorEventualFoundation.php';
require_once APPPATH . 'Database/Migrations/2026-08-25-110001_CreateVendedorEventualEnrollments.php';
require_once APPPATH . 'Database/Migrations/2026-08-26-100002_CreateVendedorEventualCatalogVersions.php';
require_once APPPATH . 'Database/Migrations/2026-08-26-100003_CreateVendedorEventualOpportunities.php';
require_once APPPATH . 'Database/Migrations/2026-09-03-100001_CreateVendedorEventualPortfolioRequests.php';

final class DuplicityAlertServiceTest extends CIUnitTestCase
{
    private $testDb;
    private CreateVendedorEventualFoundation $foundation;
    private CreateVendedorEventualEnrollments $enrollments;
    private CreateVendedorEventualCatalogVersions $catalog;
    private CreateVendedorEventualOpportunities $opportunities;
    private CreateVendedorEventualPortfolioRequests $requests;

    protected function setUp(): void
    {
        parent::setUp();
        $this->testDb        = Database::connect('tests');
        $this->foundation    = new CreateVendedorEventualFoundation(Database::forge('tests'));
        $this->enrollments   = new CreateVendedorEventualEnrollments(Database::forge('tests'));
        $this->catalog       = new CreateVendedorEventualCatalogVersions(Database::forge('tests'));
        $this->opportunities = new CreateVendedorEventualOpportunities(Database::forge('tests'));
        $this->requests      = new CreateVendedorEventualPortfolioRequests(Database::forge('tests'));

        $this->requests->down();
        $this->opportunities->down();
        $this->catalog->down();
        $this->enrollments->down();
        $this->foundation->down();

        $this->foundation->up();
        $this->enrollments->up();
        $this->catalog->up();
        $this->opportunities->up();
        $this->requests->up();

        $this->dropExternalStubs();
    }

    protected function tearDown(): void
    {
        $this->dropExternalStubs();
        $this->requests->down();
        $this->opportunities->down();
        $this->catalog->down();
        $this->enrollments->down();
        $this->foundation->down();
        parent::tearDown();
    }

    // -------------------------------------------------------------------------
    // Sem duplicidade
    // -------------------------------------------------------------------------

    public function testNoAlertsWhenNothingExists(): void
    {
        $alerts = (new DuplicityAlertService($this->testDb))
            ->check('12345678000195', 1, 0);

        $this->assertSame([], $alerts);
    }

    // -------------------------------------------------------------------------
    // portfolio_assigned
    // -------------------------------------------------------------------------

    public function testPortfolioAssignedAlert(): void
    {
        $this->createClientWalletsStub();
        $this->testDb->query("INSERT INTO db_client_wallets (cnpj, vendor_id) VALUES ('12345678000195', 42)");

        $alerts = (new DuplicityAlertService($this->testDb))
            ->check('12345678000195', 1, 0);

        $types = array_column($alerts, 'type');
        $this->assertContains('portfolio_assigned', $types);
    }

    public function testNoPortfolioAlertWhenVendorIdNull(): void
    {
        $this->createClientWalletsStub();
        $this->testDb->query("INSERT INTO db_client_wallets (cnpj, vendor_id) VALUES ('12345678000195', NULL)");

        $alerts = (new DuplicityAlertService($this->testDb))
            ->check('12345678000195', 1, 0);

        $types = array_column($alerts, 'type');
        $this->assertNotContains('portfolio_assigned', $types);
    }

    // -------------------------------------------------------------------------
    // active_opportunity
    // -------------------------------------------------------------------------

    public function testActiveOpportunityAlert(): void
    {
        $campaignId = $this->createCampaign();
        $empId      = $this->createEmployee('EMP-DA-1', 901);
        $this->testDb->table('ve_opportunities')->insert([
            'correlation_id'              => 'aaaaaaaa-0000-4000-8000-000000000001',
            'campaign_id'                 => $campaignId,
            'originator_employee_id'      => $empId,
            'current_conductor_employee_id' => $empId,
            'cnpj'                        => '12345678000195',
            'contact_context'             => 'Outro contato.',
            'channel'                     => 'presencial',
            'status'                      => 'registered',
            'contacted_at'                => '2026-09-01 10:00:00',
            'created_at'                  => '2026-09-01 10:00:00',
            'updated_at'                  => '2026-09-01 10:00:00',
        ]);
        $existingId = (int) $this->testDb->insertID();

        $alerts = (new DuplicityAlertService($this->testDb))
            ->check('12345678000195', $campaignId, 0);

        $types = array_column($alerts, 'type');
        $this->assertContains('active_opportunity', $types);

        // Excluindo a própria oportunidade não deve gerar alerta.
        $alerts2 = (new DuplicityAlertService($this->testDb))
            ->check('12345678000195', $campaignId, $existingId);

        $types2 = array_column($alerts2, 'type');
        $this->assertNotContains('active_opportunity', $types2);
    }

    // -------------------------------------------------------------------------
    // pending_reservation
    // -------------------------------------------------------------------------

    public function testPendingReservationAlert(): void
    {
        $requestDbId = $this->createRequestStub('12345678000195');
        $this->createReservationStub('res-da-001', '12345678000195', 'provisional', $requestDbId);

        $alerts = (new DuplicityAlertService($this->testDb))
            ->check('12345678000195', 1, 0);

        $types = array_column($alerts, 'type');
        $this->assertContains('pending_reservation', $types);
    }

    public function testResolvedReservationDoesNotAlert(): void
    {
        $requestDbId = $this->createRequestStub('12345678000195');
        $this->createReservationStub('res-da-002', '12345678000195', 'resolved', $requestDbId);

        $alerts = (new DuplicityAlertService($this->testDb))
            ->check('12345678000195', 1, 0);

        $types = array_column($alerts, 'type');
        $this->assertNotContains('pending_reservation', $types);
    }

    // -------------------------------------------------------------------------
    // Combinação dos três tipos ao mesmo tempo
    // -------------------------------------------------------------------------

    public function testAllThreeAlertsAtOnce(): void
    {
        $this->createClientWalletsStub();
        $this->testDb->query("INSERT INTO db_client_wallets (cnpj, vendor_id) VALUES ('12345678000195', 1)");

        $campaignId = $this->createCampaign();
        $empId      = $this->createEmployee('EMP-DA-2', 902);
        $this->testDb->table('ve_opportunities')->insert([
            'correlation_id'              => 'bbbbbbbb-0000-4000-8000-000000000001',
            'campaign_id'                 => $campaignId,
            'originator_employee_id'      => $empId,
            'current_conductor_employee_id' => $empId,
            'cnpj'                        => '12345678000195',
            'contact_context'             => 'Contexto.',
            'channel'                     => 'presencial',
            'status'                      => 'registered',
            'contacted_at'                => '2026-09-01 10:00:00',
            'created_at'                  => '2026-09-01 10:00:00',
            'updated_at'                  => '2026-09-01 10:00:00',
        ]);
        $requestDbId = $this->createRequestStub('12345678000195');
        $this->createReservationStub('res-da-003', '12345678000195', 'provisional', $requestDbId);

        $alerts = (new DuplicityAlertService($this->testDb))
            ->check('12345678000195', $campaignId, 0);

        $types = array_column($alerts, 'type');
        $this->assertContains('portfolio_assigned', $types);
        $this->assertContains('active_opportunity', $types);
        $this->assertContains('pending_reservation', $types);
        $this->assertCount(3, $alerts);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function createCampaign(): int
    {
        $this->testDb->table('ve_campaigns')->insert([
            'code'      => 'DA-CAMP-' . uniqid(),
            'name'      => 'Campanha Duplicidades',
            'mode'      => 'demonstrative',
            'status'    => 'active',
            'starts_at' => '2026-09-01 00:00:00',
            'ends_at'   => '2026-12-01 00:00:00',
        ]);

        return (int) $this->testDb->insertID();
    }

    private function createEmployee(string $code, int $shieldUserId): int
    {
        $this->testDb->table('employees')->insert([
            'employee_id'       => $code,
            'shield_user_id'    => $shieldUserId,
            'display_name'      => $code,
            'identity_source'   => 'demo',
            'employment_status' => 'active',
        ]);

        return (int) $this->testDb->insertID();
    }

    private function createClientWalletsStub(): void
    {
        $this->testDb->query('DROP TABLE IF EXISTS db_client_wallets');
        $this->testDb->query("
            CREATE TABLE db_client_wallets (
                cnpj      VARCHAR(14),
                vendor_id INTEGER DEFAULT NULL
            )
        ");
    }

    /** Cria um ve_portfolio_requests stub e retorna seu id numérico. */
    private function createRequestStub(string $cnpj): int
    {
        // Opportunity e employee mínimos para satisfazer FK.
        $campaignId = $this->createCampaign();
        $empId      = $this->createEmployee('EMP-STUB-' . uniqid(), 9000 + rand(1, 999));
        $this->testDb->table('ve_opportunities')->insert([
            'correlation_id'                => 'stub-' . uniqid() . '-4000-8000-0000000stub',
            'campaign_id'                   => $campaignId,
            'originator_employee_id'        => $empId,
            'current_conductor_employee_id' => $empId,
            'cnpj'                          => $cnpj,
            'contact_context'               => 'Stub.',
            'channel'                       => 'presencial',
            'status'                        => 'registered',
            'contacted_at'                  => '2026-09-01 10:00:00',
            'created_at'                    => '2026-09-01 10:00:00',
            'updated_at'                    => '2026-09-01 10:00:00',
        ]);
        $opportunityId = (int) $this->testDb->insertID();

        $this->testDb->table('ve_portfolio_requests')->insert([
            'request_id'     => 'req-stub-' . uniqid(),
            'opportunity_id' => $opportunityId,
            'employee_id'    => $empId,
            'cnpj'           => $cnpj,
            'status'         => 'provisional',
            'requested_at'   => '2026-09-01 10:00:00',
            'created_at'     => '2026-09-01 10:00:00',
            'updated_at'     => '2026-09-01 10:00:00',
        ]);

        return (int) $this->testDb->insertID();
    }

    private function createReservationStub(string $reservationId, string $cnpj, string $status, int $firstRequestId): void
    {
        $this->testDb->table('ve_portfolio_reservations')->insert([
            'reservation_id'   => $reservationId,
            'cnpj'             => $cnpj,
            'first_request_id' => $firstRequestId,
            'status'           => $status,
            'created_at'       => '2026-09-01 10:00:00',
            'updated_at'       => '2026-09-01 10:00:00',
        ]);
    }

    private function dropExternalStubs(): void
    {
        $this->testDb->query('DROP TABLE IF EXISTS db_client_wallets');
    }
}
