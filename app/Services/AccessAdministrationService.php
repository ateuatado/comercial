<?php

declare(strict_types=1);

namespace App\Services;

use DateTimeImmutable;
use DomainException;

class AccessAdministrationService
{
    private const CAMPAIGN_TRANSITIONS = [
        'draft' => ['published', 'archived'],
        'published' => ['active', 'suspended', 'archived'],
        'active' => ['suspended', 'closed'],
        'suspended' => ['active', 'closed'],
        'closed' => ['archived'],
        'archived' => [],
    ];

    public function createCampaign(array $data, int $actorUserId): int
    {
        $db = db_connect();
        $code = strtoupper(trim((string) ($data['code'] ?? '')));
        $name = trim((string) ($data['name'] ?? ''));
        $startsAt = $data['starts_at'] ?? null;
        $endsAt = $data['ends_at'] ?? null;

        if (! preg_match('/^[A-Z0-9_-]{3,60}$/', $code) || $name === '' || $endsAt === null) {
            throw new DomainException('Informe código, nome e término válidos para a campanha.');
        }
        if ($startsAt !== null && new DateTimeImmutable((string) $endsAt) <= new DateTimeImmutable((string) $startsAt)) {
            throw new DomainException('O término deve ser posterior ao início.');
        }
        if ($db->table('ve_campaigns')->where('code', $code)->countAllResults() > 0) {
            throw new DomainException('Já existe uma campanha com esse código.');
        }

        $now = date('Y-m-d H:i:s');
        $db->transStart();
        $db->table('ve_campaigns')->insert([
            'code' => $code,
            'name' => $name,
            'mode' => 'demonstrative',
            'status' => 'draft',
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'created_by' => $actorUserId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $campaignId = (int) $db->insertID();
        $this->audit('campaign_created', $actorUserId, null, null, $campaignId, [
            'code' => $code,
            'mode' => 'demonstrative',
        ]);
        $db->transComplete();

        if (! $db->transStatus()) {
            throw new DomainException('Não foi possível criar a campanha.');
        }

        return $campaignId;
    }

    public function setApplicationEnabled(int $applicationId, bool $enabled, int $actorUserId): void
    {
        $db = db_connect();
        if ($db->table('ve_applications')->where('id', $applicationId)->countAllResults() !== 1) {
            throw new DomainException('Aplicação não encontrada.');
        }

        $db->transStart();
        $db->table('ve_applications')->where('id', $applicationId)->update([
            'enabled' => $enabled,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $this->audit('application_status_changed', $actorUserId, null, $applicationId, null, [
            'enabled' => $enabled,
        ]);
        $db->transComplete();

        if (! $db->transStatus()) {
            throw new DomainException('Não foi possível atualizar a aplicação.');
        }
    }

    public function changeCampaignStatus(int $campaignId, string $newStatus, int $actorUserId): void
    {
        $db = db_connect();
        $campaign = $db->table('ve_campaigns')->where('id', $campaignId)->get()->getRowArray();
        if ($campaign === null) {
            throw new DomainException('Campanha não encontrada.');
        }

        $current = (string) $campaign['status'];
        if (! in_array($newStatus, self::CAMPAIGN_TRANSITIONS[$current] ?? [], true)) {
            throw new DomainException("Transição de campanha inválida: {$current} → {$newStatus}.");
        }

        $db->transStart();
        $db->table('ve_campaigns')->where('id', $campaignId)->update([
            'status' => $newStatus,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $this->audit('campaign_status_changed', $actorUserId, null, null, $campaignId, [
            'from' => $current,
            'to' => $newStatus,
        ]);
        $db->transComplete();

        if (! $db->transStatus()) {
            throw new DomainException('Não foi possível atualizar a campanha.');
        }
    }

    public function grant(array $data, int $actorUserId): int
    {
        $db = db_connect();
        $employeeId = (int) ($data['employee_id'] ?? 0);
        $applicationId = (int) ($data['application_id'] ?? 0);
        $source = (string) ($data['source'] ?? 'administrator');
        $campaignId = ! empty($data['campaign_id']) ? (int) $data['campaign_id'] : null;
        $validFrom = (string) ($data['valid_from'] ?? date('Y-m-d H:i:s'));
        $validUntil = ! empty($data['valid_until']) ? (string) $data['valid_until'] : null;

        $employee = $db->table('employees')->where('id', $employeeId)->get()->getRowArray();
        $application = $db->table('ve_applications')->where('id', $applicationId)->get()->getRowArray();
        if ($employee === null || $application === null) {
            throw new DomainException('Empregado ou aplicação inválidos.');
        }
        if (! in_array($source, ['administrator', 'campaign'], true)) {
            throw new DomainException('Origem de concessão inválida.');
        }
        if ($source === 'administrator' && $campaignId !== null) {
            throw new DomainException('Concessão administrativa deve ser explicitamente desvinculada de campanha.');
        }

        if ($source === 'campaign') {
            $campaign = $campaignId === null
                ? null
                : $db->table('ve_campaigns')->where('id', $campaignId)->get()->getRowArray();
            if ($campaign === null || empty($campaign['ends_at'])) {
                throw new DomainException('Concessões de campanha exigem campanha com término definido.');
            }
            $validUntil = $this->earliest($validUntil, (string) $campaign['ends_at']);
        }

        if ($validUntil !== null && new DateTimeImmutable($validUntil) <= new DateTimeImmutable($validFrom)) {
            throw new DomainException('O término da concessão deve ser posterior ao início.');
        }

        $db->transStart();
        $db->table('ve_employee_entitlements')->insert([
            'employee_id' => $employeeId,
            'application_id' => $applicationId,
            'campaign_id' => $campaignId,
            'capability' => (string) ($data['capability'] ?? 'access'),
            'source' => $source,
            'status' => 'active',
            'valid_from' => $validFrom,
            'valid_until' => $validUntil,
            'granted_by' => $actorUserId,
            'reason' => trim((string) ($data['reason'] ?? '')) ?: null,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $entitlementId = (int) $db->insertID();
        $this->audit('entitlement_granted', $actorUserId, $employeeId, $applicationId, $campaignId, [
            'entitlement_id' => $entitlementId,
            'source' => $source,
            'valid_until' => $validUntil,
        ]);
        $db->transComplete();

        if (! $db->transStatus()) {
            throw new DomainException('Não foi possível registrar a concessão.');
        }

        return $entitlementId;
    }

    public function revoke(int $entitlementId, int $actorUserId, string $reason): void
    {
        $db = db_connect();
        $entitlement = $db->table('ve_employee_entitlements')->where('id', $entitlementId)->get()->getRowArray();
        if ($entitlement === null || $entitlement['status'] === 'revoked') {
            throw new DomainException('Concessão ativa não encontrada.');
        }

        $now = date('Y-m-d H:i:s');
        $db->transStart();
        $db->table('ve_employee_entitlements')->where('id', $entitlementId)->update([
            'status' => 'revoked',
            'revoked_at' => $now,
            'revoked_by' => $actorUserId,
            'reason' => trim($reason) ?: $entitlement['reason'],
            'updated_at' => $now,
        ]);
        $this->audit(
            'entitlement_revoked',
            $actorUserId,
            (int) $entitlement['employee_id'],
            (int) $entitlement['application_id'],
            $entitlement['campaign_id'] !== null ? (int) $entitlement['campaign_id'] : null,
            ['entitlement_id' => $entitlementId, 'reason' => trim($reason)]
        );
        $db->transComplete();

        if (! $db->transStatus()) {
            throw new DomainException('Não foi possível revogar a concessão.');
        }
    }

    private function audit(
        string $eventType,
        int $actorUserId,
        ?int $employeeId,
        ?int $applicationId,
        ?int $campaignId,
        array $metadata
    ): void {
        db_connect()->table('ve_access_events')->insert([
            'employee_id' => $employeeId,
            'application_id' => $applicationId,
            'campaign_id' => $campaignId,
            'event_type' => $eventType,
            'actor_user_id' => $actorUserId,
            'metadata' => json_encode($metadata, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    private function earliest(?string $first, string $second): string
    {
        if ($first === null) {
            return $second;
        }

        return new DateTimeImmutable($first) <= new DateTimeImmutable($second) ? $first : $second;
    }
}
