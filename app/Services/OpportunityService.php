<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\EmployeeModel;
use DateTimeImmutable;
use DateTimeInterface;
use DomainException;

class OpportunityService
{
    public function create(int $shieldUserId, array $data, ?DateTimeInterface $at = null): int
    {
        $employee = (new EmployeeModel())->findByShieldUserId($shieldUserId);
        $campaignId = (int) ($data['campaign_id'] ?? 0);
        $cnpj = preg_replace('/\D/', '', (string) ($data['cnpj'] ?? ''));
        $context = trim((string) ($data['contact_context'] ?? ''));
        $channel = (string) ($data['channel'] ?? 'presencial');
        if ($employee === null || $employee['employment_status'] !== 'active' || $campaignId < 1) {
            throw new DomainException('Empregado ou campanha inválidos.');
        }
        if (strlen($cnpj) !== 14 || preg_match('/^(\d)\1{13}$/', $cnpj)) {
            throw new DomainException('Informe um CNPJ com 14 dígitos.');
        }
        if ($context === '' || ! in_array($channel, ['presencial', 'telefone', 'email', 'evento', 'outro'], true)) {
            throw new DomainException('Informe o contexto do contato e um canal válido.');
        }
        $enrollment = db_connect()->table('ve_enrollments')->where(['employee_id' => $employee['id'], 'campaign_id' => $campaignId, 'status' => 'qualified'])->get()->getRowArray();
        if ($enrollment === null || ! (new ApplicationAccessService())->hasAccess($shieldUserId, 'vendedor_eventual', 'access', $campaignId)) {
            throw new DomainException('Somente participação habilitada pode registrar oportunidade.');
        }
        $questionnaire = db_connect()->table('ve_questionnaire_versions')->where(['campaign_id' => $campaignId, 'status' => 'published'])->orderBy('published_at', 'DESC')->get()->getRowArray();
        $instant = $at ?? new DateTimeImmutable();
        $correlationId = $this->uuid();
        $db = db_connect();
        $db->transStart();
        $db->table('ve_opportunities')->insert([
            'correlation_id' => $correlationId, 'campaign_id' => $campaignId,
            'originator_employee_id' => $employee['id'], 'current_conductor_employee_id' => $employee['id'],
            'questionnaire_version_id' => $questionnaire['id'] ?? null, 'cnpj' => $cnpj,
            'contact_context' => $context, 'channel' => $channel, 'status' => 'registered',
            'contacted_at' => $instant->format('Y-m-d H:i:s'), 'created_at' => $instant->format('Y-m-d H:i:s'), 'updated_at' => $instant->format('Y-m-d H:i:s'),
        ]);
        $id = (int) $db->insertID();
        $this->appendEvent($db, $id, 'opportunity_registered', (int) $employee['id'], $shieldUserId, $channel, $instant, $questionnaire['version'] ?? null, ['correlation_id' => $correlationId, 'cnpj' => $cnpj]);
        $db->transComplete();
        if (! $db->transStatus()) { throw new DomainException('Não foi possível registrar a oportunidade.'); }
        return $id;
    }

    public function mine(int $shieldUserId): array
    {
        $employee = (new EmployeeModel())->findByShieldUserId($shieldUserId);
        if ($employee === null) { return []; }
        return db_connect()->table('ve_opportunities opportunity')->select('opportunity.*, campaign.name AS campaign_name')->join('ve_campaigns campaign', 'campaign.id = opportunity.campaign_id')->where('opportunity.originator_employee_id', $employee['id'])->orderBy('opportunity.created_at', 'DESC')->get()->getResultArray();
    }

    public function detailFor(int $shieldUserId, int $opportunityId): array
    {
        $employee = (new EmployeeModel())->findByShieldUserId($shieldUserId);
        $opportunity = $employee === null ? null : db_connect()->table('ve_opportunities opportunity')->select('opportunity.*, campaign.name AS campaign_name, questionnaire.version AS questionnaire_version')->join('ve_campaigns campaign', 'campaign.id = opportunity.campaign_id')->join('ve_questionnaire_versions questionnaire', 'questionnaire.id = opportunity.questionnaire_version_id', 'left')->where(['opportunity.id' => $opportunityId, 'opportunity.originator_employee_id' => $employee['id']])->get()->getRowArray();
        if ($opportunity === null) { throw new DomainException('Oportunidade não encontrada para este empregado.'); }
        $opportunity['events'] = db_connect()->table('ve_opportunity_events')->where('opportunity_id', $opportunityId)->orderBy('occurred_at', 'ASC')->get()->getResultArray();
        $opportunity['portfolio'] = (new PortfolioVisibilityService())->statusForCnpj((string) $opportunity['cnpj']);
        return $opportunity;
    }

    private function appendEvent($db, int $opportunityId, string $type, int $employeeId, int $userId, string $channel, DateTimeInterface $at, ?string $version, array $metadata): void
    {
        $db->table('ve_opportunity_events')->insert(['opportunity_id' => $opportunityId, 'event_id' => $this->uuid(), 'event_type' => $type, 'actor_employee_id' => $employeeId, 'actor_user_id' => $userId, 'channel' => $channel, 'occurred_at' => $at->format('Y-m-d H:i:s'), 'received_at' => (new DateTimeImmutable())->format('Y-m-d H:i:s'), 'content_version' => $version, 'metadata' => json_encode($metadata, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)]);
    }

    private function uuid(): string
    {
        $bytes = random_bytes(16); $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40); $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80); $hex = bin2hex($bytes);
        return substr($hex, 0, 8) . '-' . substr($hex, 8, 4) . '-' . substr($hex, 12, 4) . '-' . substr($hex, 16, 4) . '-' . substr($hex, 20);
    }
}
