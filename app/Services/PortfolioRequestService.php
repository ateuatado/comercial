<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\EmployeeModel;
use DateTimeImmutable;
use DateTimeInterface;
use DomainException;

/** Registra intenção de inclusão em carteira sem alterar a carteira operacional. */
class PortfolioRequestService
{
    public function requestForOpportunity(int $shieldUserId, int $opportunityId, ?DateTimeInterface $at = null): array
    {
        $opportunity = (new OpportunityService())->detailFor($shieldUserId, $opportunityId);
        $employee = (new EmployeeModel())->findByShieldUserId($shieldUserId);

        if ($employee === null || $employee['employment_status'] !== 'active') {
            throw new DomainException('Empregado não está apto a solicitar inclusão em carteira.');
        }

        $db = db_connect();
        $qualified = $db->table('ve_enrollments')->where([
            'employee_id' => $employee['id'],
            'campaign_id' => $opportunity['campaign_id'],
            'status' => 'qualified',
        ])->countAllResults() === 1;

        if (! $qualified || ! (new ApplicationAccessService())->hasAccess($shieldUserId, 'vendedor_eventual', 'access', (int) $opportunity['campaign_id'])) {
            throw new DomainException('Somente participação habilitada pode solicitar inclusão em carteira.');
        }

        $existing = $this->findByOpportunity($db, $opportunityId);
        if ($existing !== null) {
            $existing['reservation_created'] = false;
            return $existing;
        }

        $instant = $at ?? new DateTimeImmutable();
        $timestamp = $instant->format('Y-m-d H:i:s');
        $requestId = $this->uuid();
        $reservationCreated = false;

        $db->transStart();
        $db->table('ve_portfolio_requests')->insert([
            'request_id' => $requestId,
            'opportunity_id' => $opportunityId,
            'employee_id' => $employee['id'],
            'cnpj' => $opportunity['cnpj'],
            'status' => 'provisional',
            'requested_at' => $timestamp,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);
        $requestDbId = (int) $db->insertID();

        $reservation = $db->table('ve_portfolio_reservations')->where('cnpj', $opportunity['cnpj'])->get()->getRowArray();
        if ($reservation === null) {
            $db->table('ve_portfolio_reservations')->insert([
                'reservation_id' => $this->uuid(),
                'cnpj' => $opportunity['cnpj'],
                'first_request_id' => $requestDbId,
                'status' => 'active',
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ]);
            $reservation = $db->table('ve_portfolio_reservations')->where('id', (int) $db->insertID())->get()->getRowArray();
            $reservationCreated = true;
        }

        $db->table('ve_portfolio_requests')->where('id', $requestDbId)->update(['reservation_id' => $reservation['id'], 'updated_at' => $timestamp]);
        $this->appendEvent($db, $opportunityId, (int) $employee['id'], $shieldUserId, $instant, $requestId, (string) $reservation['reservation_id'], $reservationCreated);
        $db->transComplete();

        if (! $db->transStatus()) {
            throw new DomainException('Não foi possível registrar a solicitação provisória de carteira.');
        }

        $request = $this->findByOpportunity($db, $opportunityId);
        if ($request === null) {
            throw new DomainException('Solicitação provisória não localizada após o registro.');
        }

        $request['reservation_created'] = $reservationCreated;
        return $request;
    }

    private function findByOpportunity($db, int $opportunityId): ?array
    {
        return $db->table('ve_portfolio_requests request')
            ->select('request.*, reservation.reservation_id AS reservation_reference, reservation.status AS reservation_status')
            ->join('ve_portfolio_reservations reservation', 'reservation.id = request.reservation_id', 'left')
            ->where('request.opportunity_id', $opportunityId)
            ->get()
            ->getRowArray();
    }

    private function appendEvent($db, int $opportunityId, int $employeeId, int $shieldUserId, DateTimeInterface $at, string $requestId, string $reservationId, bool $reservationCreated): void
    {
        $db->table('ve_opportunity_events')->insert([
            'opportunity_id' => $opportunityId,
            'event_id' => $this->uuid(),
            'event_type' => 'portfolio_request_created',
            'actor_employee_id' => $employeeId,
            'actor_user_id' => $shieldUserId,
            'channel' => 'system',
            'occurred_at' => $at->format('Y-m-d H:i:s'),
            'received_at' => (new DateTimeImmutable())->format('Y-m-d H:i:s'),
            'metadata' => json_encode(['request_id' => $requestId, 'reservation_id' => $reservationId, 'reservation_created' => $reservationCreated], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
        ]);
    }

    private function uuid(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);
        $hex = bin2hex($bytes);

        return substr($hex, 0, 8) . '-' . substr($hex, 8, 4) . '-' . substr($hex, 12, 4) . '-' . substr($hex, 16, 4) . '-' . substr($hex, 20);
    }
}
