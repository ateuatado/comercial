<?php

declare(strict_types=1);

use App\Database\Migrations\CreateVendedorEventualEnrollments;
use App\Database\Migrations\CreateVendedorEventualFoundation;
use App\Services\EnrollmentService;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Database;

require_once APPPATH . 'Database/Migrations/2026-08-25-100001_CreateVendedorEventualFoundation.php';
require_once APPPATH . 'Database/Migrations/2026-08-25-110001_CreateVendedorEventualEnrollments.php';

final class EnrollmentServiceTest extends CIUnitTestCase
{
    private BaseConnection $testDb;
    private CreateVendedorEventualFoundation $foundation;
    private CreateVendedorEventualEnrollments $enrollments;
    private EnrollmentService $service;
    private int $employeeId;
    private int $campaignId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->testDb = Database::connect('tests');
        $this->foundation = new CreateVendedorEventualFoundation(Database::forge('tests'));
        $this->enrollments = new CreateVendedorEventualEnrollments(Database::forge('tests'));
        $this->enrollments->down();
        $this->foundation->down();
        $this->foundation->up();
        $this->enrollments->up();
        $this->service = new EnrollmentService();

        $this->testDb->table('employees')->insert([
            'employee_id' => 'EMP-ENROLL',
            'display_name' => 'Empregado Adesão',
            'identity_source' => 'demo',
            'employment_status' => 'active',
        ]);
        $this->employeeId = (int) $this->testDb->insertID();
        $this->testDb->table('ve_campaigns')->insert([
            'code' => 'ADESAO-01',
            'name' => 'Campanha de Adesão',
            'mode' => 'demonstrative',
            'status' => 'active',
            'starts_at' => '2026-08-01 00:00:00',
            'ends_at' => '2026-09-01 00:00:00',
        ]);
        $this->campaignId = (int) $this->testDb->insertID();
    }

    protected function tearDown(): void
    {
        $this->enrollments->down();
        $this->foundation->down();
        parent::tearDown();
    }

    public function testStartsVoluntaryEnrollmentOnlyOnce(): void
    {
        $id = $this->service->start($this->employeeId, $this->campaignId, new DateTimeImmutable('2026-08-25 10:00:00'));
        $this->assertGreaterThan(0, $id);
        $this->expectException(DomainException::class);
        $this->service->start($this->employeeId, $this->campaignId, new DateTimeImmutable('2026-08-25 10:01:00'));
    }

    public function testInactiveEmployeeCannotEnroll(): void
    {
        $this->testDb->table('employees')->where('id', $this->employeeId)->update(['employment_status' => 'inactive']);
        $this->expectException(DomainException::class);
        $this->service->start($this->employeeId, $this->campaignId, new DateTimeImmutable('2026-08-25 10:00:00'));
    }

    public function testQualificationRecordsVersionedEvidenceWithoutMonetaryData(): void
    {
        $id = $this->service->start($this->employeeId, $this->campaignId, new DateTimeImmutable('2026-08-25 10:00:00'));
        $this->service->qualify($id, [
            'terms_version' => 'terms-v1',
            'training_version' => 'training-v1',
            'assessment_score' => 85,
            'assessment_passed' => true,
        ], new DateTimeImmutable('2026-08-25 11:00:00'));

        $row = $this->testDb->table('ve_enrollments')->where('id', $id)->get()->getRowArray();
        $this->assertSame('qualified', $row['status']);
        $this->assertSame('terms-v1', $row['terms_version']);
        $this->assertArrayNotHasKey('amount', $row);
    }

    public function testQualificationRejectsMissingOrFailedEvidence(): void
    {
        $id = $this->service->start($this->employeeId, $this->campaignId, new DateTimeImmutable('2026-08-25 10:00:00'));
        $this->expectException(DomainException::class);
        $this->service->qualify($id, [
            'terms_version' => 'terms-v1',
            'training_version' => 'training-v1',
            'assessment_score' => 40,
            'assessment_passed' => false,
        ], new DateTimeImmutable('2026-08-25 11:00:00'));
    }
}
