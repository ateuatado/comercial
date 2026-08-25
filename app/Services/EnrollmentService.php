<?php

declare(strict_types=1);

namespace App\Services;

use DateTimeImmutable;
use DateTimeInterface;
use DomainException;

class EnrollmentService
{
    public function start(int $employeeId, int $campaignId, ?DateTimeInterface $at = null): int
    {
        $instant = $at ?? new DateTimeImmutable();
        $this->assertEligibleEmployee($employeeId);
        $this->assertOpenCampaign($campaignId, $instant);

        $db = db_connect();
        $existing = $db->table('ve_enrollments')
            ->where(['employee_id' => $employeeId, 'campaign_id' => $campaignId])
            ->get()->getRowArray();
        if ($existing !== null) {
            throw new DomainException('O empregado já iniciou adesão a esta campanha.');
        }

        $db->table('ve_enrollments')->insert([
            'employee_id' => $employeeId,
            'campaign_id' => $campaignId,
            'status' => 'started',
            'created_at' => $instant->format('Y-m-d H:i:s'),
            'updated_at' => $instant->format('Y-m-d H:i:s'),
        ]);

        return (int) $db->insertID();
    }

    public function qualify(int $enrollmentId, array $evidence, ?DateTimeInterface $at = null): void
    {
        $instant = $at ?? new DateTimeImmutable();
        $db = db_connect();
        $enrollment = $db->table('ve_enrollments')->where('id', $enrollmentId)->get()->getRowArray();
        if ($enrollment === null || in_array($enrollment['status'], ['suspended', 'closed'], true)) {
            throw new DomainException('Adesão inexistente ou indisponível para habilitação.');
        }

        $this->assertEligibleEmployee((int) $enrollment['employee_id']);
        $this->assertOpenCampaign((int) $enrollment['campaign_id'], $instant);

        $termsVersion = trim((string) ($evidence['terms_version'] ?? ''));
        $trainingVersion = trim((string) ($evidence['training_version'] ?? ''));
        $score = $evidence['assessment_score'] ?? null;
        if ($termsVersion === '' || $trainingVersion === '' || ! is_numeric($score)) {
            throw new DomainException('Termos, treinamento e avaliação são obrigatórios.');
        }
        $numericScore = (float) $score;
        if ($numericScore < 0 || $numericScore > 100 || ($evidence['assessment_passed'] ?? false) !== true) {
            throw new DomainException('A avaliação deve estar aprovada e possuir nota entre 0 e 100.');
        }

        $db->table('ve_enrollments')->where('id', $enrollmentId)->update([
            'status' => 'qualified',
            'terms_version' => $termsVersion,
            'terms_accepted_at' => $instant->format('Y-m-d H:i:s'),
            'training_version' => $trainingVersion,
            'training_completed_at' => $instant->format('Y-m-d H:i:s'),
            'assessment_score' => $numericScore,
            'assessment_passed' => true,
            'qualified_until' => $evidence['qualified_until'] ?? null,
            'enrolled_at' => $instant->format('Y-m-d H:i:s'),
            'status_reason' => null,
            'updated_at' => $instant->format('Y-m-d H:i:s'),
        ]);
    }

    private function assertEligibleEmployee(int $employeeId): void
    {
        $employee = db_connect()->table('employees')->where('id', $employeeId)->get()->getRowArray();
        if ($employee === null || $employee['employment_status'] !== 'active') {
            throw new DomainException('Somente empregado ativo pode aderir voluntariamente.');
        }
    }

    private function assertOpenCampaign(int $campaignId, DateTimeInterface $at): void
    {
        $campaign = db_connect()->table('ve_campaigns')->where('id', $campaignId)->get()->getRowArray();
        $instant = $at->format('Y-m-d H:i:s');
        if ($campaign === null || $campaign['status'] !== 'active'
            || ($campaign['starts_at'] !== null && $campaign['starts_at'] > $instant)
            || ($campaign['ends_at'] !== null && $campaign['ends_at'] <= $instant)) {
            throw new DomainException('A campanha não está ativa e vigente.');
        }
    }
}
