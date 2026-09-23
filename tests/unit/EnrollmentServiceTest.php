<?php

declare(strict_types=1);

use App\Database\Migrations\CreateVendedorEventualEnrollments;
use App\Database\Migrations\CreateVendedorEventualFoundation;
use App\Database\Migrations\CreateVendedorEventualLearningVersions;
use App\Services\EnrollmentService;
use App\Services\EnrollmentJourneyService;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Database;
use Config\VendedorEventual;

require_once APPPATH . 'Database/Migrations/2026-08-25-100001_CreateVendedorEventualFoundation.php';
require_once APPPATH . 'Database/Migrations/2026-08-25-110001_CreateVendedorEventualEnrollments.php';
require_once APPPATH . 'Database/Migrations/2026-08-26-100001_CreateVendedorEventualLearningVersions.php';

final class EnrollmentServiceTest extends CIUnitTestCase
{
    private BaseConnection $testDb;
    private CreateVendedorEventualFoundation $foundation;
    private CreateVendedorEventualEnrollments $enrollments;
    private CreateVendedorEventualLearningVersions $learningVersions;
    private EnrollmentService $service;
    private int $employeeId;
    private int $campaignId;
    private int $applicationId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->testDb = Database::connect('tests');
        $this->foundation = new CreateVendedorEventualFoundation(Database::forge('tests'));
        $this->enrollments = new CreateVendedorEventualEnrollments(Database::forge('tests'));
        $this->learningVersions = new CreateVendedorEventualLearningVersions(Database::forge('tests'));
        $this->learningVersions->down();
        $this->enrollments->down();
        $this->foundation->down();
        $this->foundation->up();
        $this->enrollments->up();
        $this->learningVersions->up();
        $this->service = new EnrollmentService();

        $this->testDb->table('employees')->insert([
            'employee_id' => 'EMP-ENROLL',
            'shield_user_id' => 501,
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
        $application = $this->testDb->table('ve_applications')->where('code', 'vendedor_eventual')->get()->getRowArray();
        $this->applicationId = (int) $application['id'];
        $this->testDb->table('ve_applications')->where('id', $this->applicationId)->update(['enabled' => true]);
        config(VendedorEventual::class)->enabled = true;
    }

    protected function tearDown(): void
    {
        config(VendedorEventual::class)->enabled = false;
        $this->learningVersions->down();
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

    public function testQualifiedParticipationCanBePausedResumedAndClosedWithAudit(): void
    {
        $id = $this->qualifiedEnrollment();
        $this->service->pause($id, 900, new DateTimeImmutable('2026-08-25 12:00:00'));
        $this->assertSame('paused', $this->enrollmentStatus($id));
        $this->service->resume($id, 900, new DateTimeImmutable('2026-08-25 13:00:00'));
        $this->assertSame('qualified', $this->enrollmentStatus($id));
        $this->service->close($id, 900, new DateTimeImmutable('2026-08-25 14:00:00'));
        $this->assertSame('closed', $this->enrollmentStatus($id));
        $this->assertSame(3, $this->testDb->table('ve_access_events')->where('employee_id', $this->employeeId)->countAllResults());
    }

    public function testAdministrativeSuspensionRequiresReason(): void
    {
        $id = $this->qualifiedEnrollment();
        $this->expectException(DomainException::class);
        $this->service->suspend($id, 900, '   ', new DateTimeImmutable('2026-08-25 12:00:00'));
    }

    public function testAdministrativeSuspensionStoresReasonAndAudit(): void
    {
        $id = $this->qualifiedEnrollment();
        $this->service->suspend($id, 900, 'Apuração administrativa.', new DateTimeImmutable('2026-08-25 12:00:00'));
        $row = $this->testDb->table('ve_enrollments')->where('id', $id)->get()->getRowArray();
        $this->assertSame('suspended', $row['status']);
        $this->assertSame('Apuração administrativa.', $row['status_reason']);
        $this->assertSame(1, $this->testDb->table('ve_access_events')->where('event_type', 'enrollment_suspended')->countAllResults());
    }

    public function testJourneyListsOnlyCampaignWithEffectiveGrant(): void
    {
        $this->insertCampaignEntitlement();

        $campaigns = (new EnrollmentJourneyService())->campaignsFor(501, new DateTimeImmutable('2026-08-25 10:00:00'));

        $this->assertCount(1, $campaigns);
        $this->assertSame($this->campaignId, (int) $campaigns[0]['id']);
        $this->assertNull($campaigns[0]['enrollment_id']);
    }

    public function testJourneyDoesNotListPublishedCampaignBeforeActivation(): void
    {
        $this->insertCampaignEntitlement();
        $this->testDb->table('ve_campaigns')->where('id', $this->campaignId)->update(['status' => 'published']);

        $this->assertSame([], (new EnrollmentJourneyService())->campaignsFor(501, new DateTimeImmutable('2026-08-25 10:00:00')));
    }

    public function testJourneyRejectsEnrollmentWithoutCampaignGrant(): void
    {
        $this->expectException(DomainException::class);
        (new EnrollmentJourneyService())->start(501, $this->campaignId, new DateTimeImmutable('2026-08-25 10:00:00'));
    }

    public function testJourneyStartsEnrollmentAndReportsPendingStatus(): void
    {
        $this->insertCampaignEntitlement();
        $journey = new EnrollmentJourneyService();
        $journey->start(501, $this->campaignId, new DateTimeImmutable('2026-08-25 10:00:00'));

        $campaigns = $journey->campaignsFor(501, new DateTimeImmutable('2026-08-25 10:01:00'));
        $this->assertNotNull($campaigns[0]['enrollment_id']);
        $this->assertSame('started', $campaigns[0]['enrollment_status']);
    }

    private function insertCampaignEntitlement(): void
    {
        $this->testDb->table('ve_employee_entitlements')->insert([
            'employee_id' => $this->employeeId,
            'application_id' => $this->applicationId,
            'campaign_id' => $this->campaignId,
            'capability' => 'access',
            'source' => 'campaign',
            'status' => 'active',
            'valid_from' => '2026-08-01 00:00:00',
            'valid_until' => '2026-09-01 00:00:00',
        ]);
    }

    private function qualifiedEnrollment(): int
    {
        $id = $this->service->start($this->employeeId, $this->campaignId, new DateTimeImmutable('2026-08-25 10:00:00'));
        $this->service->qualify($id, ['terms_version' => 'v1', 'training_version' => 'v1', 'assessment_score' => 100, 'assessment_passed' => true], new DateTimeImmutable('2026-08-25 11:00:00'));
        return $id;
    }

    private function enrollmentStatus(int $id): string
    {
        return (string) $this->testDb->table('ve_enrollments')->select('status')->where('id', $id)->get()->getRow('status');
    }
}
