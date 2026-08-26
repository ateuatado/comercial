<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\EmployeeModel;
use DateTimeImmutable;
use DateTimeInterface;
use DomainException;

class EnrollmentJourneyService
{
    /** @return array<int, array<string, mixed>> */
    public function campaignsFor(int $shieldUserId, ?DateTimeInterface $at = null): array
    {
        $employee = (new EmployeeModel())->findByShieldUserId($shieldUserId);
        if ($employee === null || $employee['employment_status'] !== 'active') {
            return [];
        }

        $campaigns = db_connect()->table('ve_employee_entitlements ent')
            ->select('campaign.id, campaign.code, campaign.name, campaign.mode, campaign.starts_at, campaign.ends_at, enrollment.id AS enrollment_id, enrollment.status AS enrollment_status, enrollment.created_at AS enrollment_started_at')
            ->join('ve_campaigns campaign', 'campaign.id = ent.campaign_id')
            ->join('ve_enrollments enrollment', 'enrollment.campaign_id = campaign.id AND enrollment.employee_id = ent.employee_id', 'left')
            ->join('ve_applications application', 'application.id = ent.application_id')
            ->where('ent.employee_id', $employee['id'])
            ->where('application.code', 'vendedor_eventual')
            ->where('ent.capability', 'access')
            ->where('ent.source', 'campaign')
            ->groupBy('campaign.id, campaign.code, campaign.name, campaign.mode, campaign.starts_at, campaign.ends_at, enrollment.id, enrollment.status, enrollment.created_at')
            ->orderBy('campaign.starts_at', 'ASC')
            ->get()->getResultArray();

        $access = new ApplicationAccessService();
        return array_values(array_filter($campaigns, static fn (array $campaign): bool => $access->hasAccess(
            $shieldUserId,
            'vendedor_eventual',
            'access',
            (int) $campaign['id'],
            $at
        )));
    }

    public function start(int $shieldUserId, int $campaignId, ?DateTimeInterface $at = null): int
    {
        $instant = $at ?? new DateTimeImmutable();
        if (! (new ApplicationAccessService())->hasAccess(
            $shieldUserId,
            'vendedor_eventual',
            'access',
            $campaignId,
            $instant
        )) {
            throw new DomainException('A campanha não está disponível para este empregado.');
        }

        $employee = (new EmployeeModel())->findByShieldUserId($shieldUserId);
        if ($employee === null) {
            throw new DomainException('Vínculo funcional não localizado.');
        }

        return (new EnrollmentService())->start((int) $employee['id'], $campaignId, $instant);
    }
}
