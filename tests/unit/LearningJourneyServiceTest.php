<?php

declare(strict_types=1);

use App\Database\Migrations\CreateVendedorEventualEnrollments;
use App\Database\Migrations\CreateVendedorEventualFoundation;
use App\Database\Migrations\CreateVendedorEventualLearningVersions;
use App\Services\EnrollmentService;
use App\Services\LearningJourneyService;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Database;
use Config\VendedorEventual;

require_once APPPATH . 'Database/Migrations/2026-08-25-100001_CreateVendedorEventualFoundation.php';
require_once APPPATH . 'Database/Migrations/2026-08-25-110001_CreateVendedorEventualEnrollments.php';
require_once APPPATH . 'Database/Migrations/2026-08-26-100001_CreateVendedorEventualLearningVersions.php';

final class LearningJourneyServiceTest extends CIUnitTestCase
{
    private $testDb;
    private array $testMigrations;
    private int $campaignId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->testDb = Database::connect('tests');
        $this->testMigrations = [
            new CreateVendedorEventualFoundation(Database::forge('tests')),
            new CreateVendedorEventualEnrollments(Database::forge('tests')),
            new CreateVendedorEventualLearningVersions(Database::forge('tests')),
        ];
        foreach (array_reverse($this->testMigrations) as $migration) { $migration->down(); }
        foreach ($this->testMigrations as $migration) { $migration->up(); }
        config(VendedorEventual::class)->enabled = true;

        $this->testDb->table('employees')->insert(['employee_id' => 'EMP-LEARN', 'shield_user_id' => 700, 'display_name' => 'Empregado', 'identity_source' => 'demo', 'employment_status' => 'active']);
        $employeeId = (int) $this->testDb->insertID();
        $this->testDb->table('ve_campaigns')->insert(['code' => 'LEARN-01', 'name' => 'Campanha', 'mode' => 'demonstrative', 'status' => 'active', 'starts_at' => '2026-08-01 00:00:00', 'ends_at' => '2026-09-01 00:00:00']);
        $this->campaignId = (int) $this->testDb->insertID();
        $app = $this->testDb->table('ve_applications')->where('code', 'vendedor_eventual')->get()->getRowArray();
        $this->testDb->table('ve_applications')->where('id', $app['id'])->update(['enabled' => true]);
        $this->testDb->table('ve_employee_entitlements')->insert(['employee_id' => $employeeId, 'application_id' => $app['id'], 'campaign_id' => $this->campaignId, 'capability' => 'access', 'source' => 'campaign', 'status' => 'active', 'valid_from' => '2026-08-01 00:00:00', 'valid_until' => '2026-09-01 00:00:00']);
        (new EnrollmentService())->start($employeeId, $this->campaignId, new DateTimeImmutable('2026-08-25 10:00:00'));
    }

    protected function tearDown(): void
    {
        config(VendedorEventual::class)->enabled = false;
        foreach (array_reverse($this->testMigrations) as $migration) { $migration->down(); }
        parent::tearDown();
    }

    public function testPublishedContentQualifiesOnlyWithCorrectAnswerAndTerms(): void
    {
        $service = new LearningJourneyService();
        $id = $service->createVersion(['campaign_id' => $this->campaignId, 'version' => 'v1', 'title' => 'Capacitação', 'training_content' => 'Conteúdo', 'terms_content' => 'Termos', 'assessment_question' => 'Correta?', 'assessment_options' => ['Sim', 'Não'], 'correct_option' => 0], 900);
        $service->publish($id);
        $service->complete(700, $this->campaignId, 0, true);

        $row = $this->testDb->table('ve_enrollments')->where('campaign_id', $this->campaignId)->get()->getRowArray();
        $this->assertSame('qualified', $row['status']);
        $this->assertSame('v1', $row['terms_version']);
        $this->assertSame('v1', $row['training_version']);
    }
}
