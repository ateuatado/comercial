<?php

declare(strict_types=1);

use App\Database\Migrations\CreateVendedorEventualCatalogVersions;
use App\Database\Migrations\CreateVendedorEventualEnrollments;
use App\Database\Migrations\CreateVendedorEventualFoundation;
use App\Database\Migrations\CreateVendedorEventualOpportunities;
use App\Database\Migrations\CreateVendedorEventualPortfolioRequests;
use App\Services\OpportunityService;
use App\Services\PortfolioRequestService;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Database;
use Config\VendedorEventual;

require_once APPPATH . 'Database/Migrations/2026-08-25-100001_CreateVendedorEventualFoundation.php';
require_once APPPATH . 'Database/Migrations/2026-08-25-110001_CreateVendedorEventualEnrollments.php';
require_once APPPATH . 'Database/Migrations/2026-08-26-100002_CreateVendedorEventualCatalogVersions.php';
require_once APPPATH . 'Database/Migrations/2026-08-26-100003_CreateVendedorEventualOpportunities.php';
require_once APPPATH . 'Database/Migrations/2026-09-03-100001_CreateVendedorEventualPortfolioRequests.php';

final class PortfolioRequestServiceTest extends CIUnitTestCase
{
    public function testRequestCreatesOnlyModuleRecordsAndSharesTheFirstReservation(): void
    {
        $db = Database::connect('tests');
        $foundation = new CreateVendedorEventualFoundation(Database::forge('tests'));
        $enrollments = new CreateVendedorEventualEnrollments(Database::forge('tests'));
        $catalog = new CreateVendedorEventualCatalogVersions(Database::forge('tests'));
        $opportunities = new CreateVendedorEventualOpportunities(Database::forge('tests'));
        $requests = new CreateVendedorEventualPortfolioRequests(Database::forge('tests'));
        $requests->down(); $opportunities->down(); $catalog->down(); $enrollments->down(); $foundation->down();
        $foundation->up(); $enrollments->up(); $catalog->up(); $opportunities->up(); $requests->up();
        $db->query('CREATE TABLE ' . $db->prefixTable('client_wallets') . ' (cnpj VARCHAR(14))');

        config(VendedorEventual::class)->enabled = true;
        $campaignId = $this->createCampaign($db);
        $this->createQualifiedEmployee($db, 'EMP-REQ-1', 801, $campaignId);
        $this->createQualifiedEmployee($db, 'EMP-REQ-2', 802, $campaignId);
        $opportunity = new OpportunityService();
        $firstOpportunity = $opportunity->create(801, ['campaign_id' => $campaignId, 'cnpj' => '12.345.678/0001-90', 'contact_context' => 'Contato de campo.', 'channel' => 'presencial']);
        $secondOpportunity = $opportunity->create(802, ['campaign_id' => $campaignId, 'cnpj' => '12.345.678/0001-90', 'contact_context' => 'Outro contato legítimo.', 'channel' => 'evento']);

        $service = new PortfolioRequestService();
        $firstRequest = $service->requestForOpportunity(801, $firstOpportunity, new DateTimeImmutable('2026-09-03 10:00:00'));
        $secondRequest = $service->requestForOpportunity(802, $secondOpportunity, new DateTimeImmutable('2026-09-03 10:01:00'));

        $this->assertTrue($firstRequest['reservation_created']);
        $this->assertFalse($secondRequest['reservation_created']);
        $this->assertSame('provisional', $firstRequest['status']);
        $this->assertSame($firstRequest['reservation_reference'], $secondRequest['reservation_reference']);
        $this->assertSame(2, $db->table('ve_portfolio_requests')->countAllResults());
        $this->assertSame(1, $db->table('ve_portfolio_reservations')->countAllResults());
        $this->assertSame(0, $db->table('client_wallets')->countAllResults());
        $this->assertSame(2, $db->table('ve_opportunity_events')->where('event_type', 'portfolio_request_created')->countAllResults());

        config(VendedorEventual::class)->enabled = false;
        $requests->down(); $opportunities->down(); $catalog->down(); $enrollments->down(); $foundation->down();
    }

    private function createCampaign($db): int
    {
        $db->table('ve_campaigns')->insert(['code' => 'REQ-1', 'name' => 'Solicitações', 'mode' => 'demonstrative', 'status' => 'active', 'starts_at' => '2026-09-01 00:00:00', 'ends_at' => '2026-10-01 00:00:00']);

        return (int) $db->insertID();
    }

    private function createQualifiedEmployee($db, string $employeeCode, int $shieldUserId, int $campaignId): int
    {
        $db->table('employees')->insert(['employee_id' => $employeeCode, 'shield_user_id' => $shieldUserId, 'display_name' => $employeeCode, 'identity_source' => 'demo', 'employment_status' => 'active']);
        $employeeId = (int) $db->insertID();
        $application = $db->table('ve_applications')->where('code', 'vendedor_eventual')->get()->getRowArray();
        $db->table('ve_applications')->where('id', $application['id'])->update(['enabled' => true]);
        $db->table('ve_employee_entitlements')->insert(['employee_id' => $employeeId, 'application_id' => $application['id'], 'campaign_id' => $campaignId, 'capability' => 'access', 'source' => 'campaign', 'status' => 'active', 'valid_from' => '2026-09-01 00:00:00', 'valid_until' => '2026-10-01 00:00:00']);
        $db->table('ve_enrollments')->insert(['employee_id' => $employeeId, 'campaign_id' => $campaignId, 'status' => 'qualified', 'created_at' => '2026-09-01 00:00:00', 'updated_at' => '2026-09-01 00:00:00']);

        return $employeeId;
    }
}
