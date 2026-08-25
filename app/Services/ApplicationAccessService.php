<?php

declare(strict_types=1);

namespace App\Services;

use Config\VendedorEventual;
use DateTimeImmutable;
use DateTimeInterface;

class ApplicationAccessService
{
    public function hasAccess(
        int $shieldUserId,
        string $applicationCode,
        string $capability = 'access',
        ?int $campaignId = null,
        ?DateTimeInterface $at = null
    ): bool {
        if (! $this->featureAllows($applicationCode)) {
            return false;
        }

        $instant = ($at ?? new DateTimeImmutable())->format('Y-m-d H:i:s');
        $builder = db_connect()->table('ve_employee_entitlements ent')
            ->select('ent.id')
            ->join('employees emp', 'emp.id = ent.employee_id')
            ->join('ve_applications app', 'app.id = ent.application_id')
            ->join('ve_campaigns campaign', 'campaign.id = ent.campaign_id', 'left')
            ->where('emp.shield_user_id', $shieldUserId)
            ->where('emp.employment_status', 'active')
            ->where('app.code', $applicationCode)
            ->where('app.enabled', true)
            ->where('ent.capability', $capability)
            ->where('ent.status', 'active')
            ->where('ent.revoked_at', null)
            ->where('ent.valid_from <=', $instant)
            ->groupStart()
                ->where('ent.valid_until', null)
                ->orWhere('ent.valid_until >', $instant)
            ->groupEnd()
            ->groupStart()
                ->where('ent.source !=', 'campaign')
                ->orGroupStart()
                    ->where('campaign.status', 'active')
                    ->groupStart()
                        ->where('campaign.starts_at', null)
                        ->orWhere('campaign.starts_at <=', $instant)
                    ->groupEnd()
                    ->groupStart()
                        ->where('campaign.ends_at', null)
                        ->orWhere('campaign.ends_at >', $instant)
                    ->groupEnd()
                ->groupEnd()
            ->groupEnd();

        if ($campaignId !== null) {
            $builder->where('ent.campaign_id', $campaignId);
        }

        return $builder->limit(1)->countAllResults() > 0;
    }

    /** @return array<int, array<string, mixed>> */
    public function applicationsFor(int $shieldUserId, ?DateTimeInterface $at = null): array
    {
        $applications = db_connect()->table('ve_applications')
            ->select('code, name, description')
            ->where('enabled', true)
            ->orderBy('name', 'ASC')
            ->get()->getResultArray();

        return array_values(array_filter(
            $applications,
            fn (array $application): bool => $this->hasAccess(
                $shieldUserId,
                (string) $application['code'],
                'access',
                null,
                $at
            )
        ));
    }

    private function featureAllows(string $applicationCode): bool
    {
        if ($applicationCode !== 'vendedor_eventual') {
            return true;
        }

        /** @var VendedorEventual $config */
        $config = config(VendedorEventual::class);
        return $config->enabled;
    }
}
