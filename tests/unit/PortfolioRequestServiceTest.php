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
    private $testDb;
    private CreateVendedorEventualFoundation $foundation;
    private CreateVendedorEventualEnrollments $enrollments;
    private CreateVendedorEventualCatalogVersions $catalog;
    private CreateVendedorEventualOpportunities $opportunities;
    private CreateVendedorEventualPortfolioRequests $requests;

    protected function setUp(): void
    {
        parent::setUp();

        $this->testDb      = Database::connect('tests');
        $this->foundation  = new CreateVendedorEventualFoundation(Database::forge('tests'));
        $this->enrollments = new CreateVendedorEventualEnrollments(Database::forge('tests'));
        $this->catalog     = new CreateVendedorEventualCatalogVersions(Database::forge('tests'));
        $this->opportunities = new CreateVendedorEventualOpportunities(Database::forge('tests'));
        $this->requests    = new CreateVendedorEventualPortfolioRequests(Database::forge('tests'));

        // Derruba em ordem inversa de dependências para garantir estado limpo.
        $this->requests->down();
        $this->opportunities->down();
        $this->catalog->down();
        $this->enrollments->down();
        $this->foundation->down();

        // Reconstrói na ordem correta.
        $this->foundation->up();
        $this->enrollments->up();
        $this->catalog->up();
        $this->opportunities->up();
        $this->requests->up();

        // Tabela stub de carteira — usada apenas para confirmar que o serviço
        // não escreve nela. DROP IF EXISTS garante idempotência entre testes.
        // Inclui colunas necessárias para PortfolioVisibilityService não falhar no JOIN.
        $this->testDb->query('DROP TABLE IF EXISTS ' . $this->testDb->prefixTable('client_wallets'));
        $this->testDb->query('DROP TABLE IF EXISTS ' . $this->testDb->prefixTable('vendors'));
        $this->testDb->query('CREATE TABLE ' . $this->testDb->prefixTable('vendors') . ' (id INTEGER PRIMARY KEY, nome VARCHAR(150), lotacao VARCHAR(150))');
        $this->testDb->query('CREATE TABLE ' . $this->testDb->prefixTable('client_wallets') . ' (id INTEGER PRIMARY KEY AUTOINCREMENT, cnpj VARCHAR(14), vendor_id INTEGER DEFAULT NULL, status_operacional VARCHAR(50) DEFAULT NULL, origem_atribuicao VARCHAR(50) DEFAULT NULL)');
    }

    protected function tearDown(): void
    {
        $this->testDb->query('DROP TABLE IF EXISTS ' . $this->testDb->prefixTable('client_wallets'));
        $this->requests->down();
        $this->opportunities->down();
        $this->catalog->down();
        $this->enrollments->down();
        $this->foundation->down();

        parent::tearDown();
    }

    public function testRequestCreatesOnlyModuleRecordsAndSharesTheFirstReservation(): void
    {
        config(VendedorEventual::class)->enabled = true;

        $campaignId = $this->createCampaign();
        $this->createQualifiedEmployee('EMP-REQ-1', 801, $campaignId);
        $this->createQualifiedEmployee('EMP-REQ-2', 802, $campaignId);

        $opportunity     = new OpportunityService();
        $firstOpportunity  = $opportunity->create(801, ['campaign_id' => $campaignId, 'cnpj' => '12.345.678/0001-90', 'contact_context' => 'Contato de campo.', 'channel' => 'presencial', 'cnpj_confirmed' => '1']);
        $secondOpportunity = $opportunity->create(802, ['campaign_id' => $campaignId, 'cnpj' => '12.345.678/0001-90', 'contact_context' => 'Outro contato legítimo.', 'channel' => 'evento', 'cnpj_confirmed' => '1']);

        $service       = new PortfolioRequestService();
        $firstRequest  = $service->requestForOpportunity(801, $firstOpportunity,  new DateTimeImmutable('2026-09-03 10:00:00'));
        $secondRequest = $service->requestForOpportunity(802, $secondOpportunity, new DateTimeImmutable('2026-09-03 10:01:00'));

        $this->assertTrue($firstRequest['reservation_created']);
        $this->assertFalse($secondRequest['reservation_created']);
        $this->assertSame('provisional', $firstRequest['status']);
        $this->assertSame($firstRequest['reservation_reference'], $secondRequest['reservation_reference']);
        $this->assertSame(2, $this->testDb->table('ve_portfolio_requests')->countAllResults());
        $this->assertSame(1, $this->testDb->table('ve_portfolio_reservations')->countAllResults());
        $this->assertSame(0, $this->testDb->table('client_wallets')->countAllResults());
        $this->assertSame(2, $this->testDb->table('ve_opportunity_events')->where('event_type', 'portfolio_request_created')->countAllResults());

        config(VendedorEventual::class)->enabled = false;
    }

    private function createCampaign(): int
    {
        $this->testDb->table('ve_campaigns')->insert([
            'code'      => 'REQ-1',
            'name'      => 'Solicitações',
            'mode'      => 'demonstrative',
            'status'    => 'active',
            'starts_at' => '2026-09-01 00:00:00',
            'ends_at'   => '2026-10-01 00:00:00',
        ]);

        return (int) $this->testDb->insertID();
    }

    private function createQualifiedEmployee(string $employeeCode, int $shieldUserId, int $campaignId): int
    {
        $this->testDb->table('employees')->insert([
            'employee_id'     => $employeeCode,
            'shield_user_id'  => $shieldUserId,
            'display_name'    => $employeeCode,
            'identity_source' => 'demo',
            'employment_status' => 'active',
        ]);
        $employeeId  = (int) $this->testDb->insertID();
        $application = $this->testDb->table('ve_applications')->where('code', 'vendedor_eventual')->get()->getRowArray();

        $this->testDb->table('ve_applications')->where('id', $application['id'])->update(['enabled' => true]);
        $this->testDb->table('ve_employee_entitlements')->insert([
            'employee_id'    => $employeeId,
            'application_id' => $application['id'],
            'campaign_id'    => $campaignId,
            'capability'     => 'access',
            'source'         => 'campaign',
            'status'         => 'active',
            'valid_from'     => '2026-09-01 00:00:00',
            'valid_until'    => '2026-10-01 00:00:00',
        ]);
        $this->testDb->table('ve_enrollments')->insert([
            'employee_id' => $employeeId,
            'campaign_id' => $campaignId,
            'status'      => 'qualified',
            'created_at'  => '2026-09-01 00:00:00',
            'updated_at'  => '2026-09-01 00:00:00',
        ]);

        return $employeeId;
    }
}
