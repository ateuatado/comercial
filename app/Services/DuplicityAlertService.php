<?php

declare(strict_types=1);

namespace App\Services;

use CodeIgniter\Database\BaseConnection;

/**
 * Detecta duplicidades relacionadas a um CNPJ no contexto de uma campanha.
 *
 * Três ângulos verificados:
 *  - portfolio_assigned:    CNPJ já está em client_wallets com responsável.
 *  - active_opportunity:    mesmo CNPJ + mesma campanha tem oportunidade aberta de outro empregado.
 *  - pending_reservation:   CNPJ já tem reserva técnica em ve_portfolio_reservations.
 *
 * Nunca lança exceção. Nunca bloqueia. Retorna lista vazia quando não há conflito.
 * Usa try/catch em vez de tableExists() por compatibilidade com SQLite in-memory nos testes.
 */
class DuplicityAlertService
{
    public function __construct(private ?BaseConnection $db = null)
    {
        $this->db ??= db_connect();
    }

    /**
     * @param string $cnpj                  CNPJ (com ou sem máscara).
     * @param int    $campaignId            Campanha da oportunidade sendo registrada.
     * @param int    $excludeOpportunityId  ID da própria oportunidade (excluída da busca de ativas).
     *
     * @return list<array{type: string, label: string, detail: string|null}>
     */
    public function check(string $cnpj, int $campaignId, int $excludeOpportunityId = 0): array
    {
        $cnpj   = preg_replace('/\D/', '', $cnpj);
        $alerts = [];

        $alerts = array_merge($alerts, $this->checkPortfolio($cnpj));
        $alerts = array_merge($alerts, $this->checkActiveOpportunity($cnpj, $campaignId, $excludeOpportunityId));
        $alerts = array_merge($alerts, $this->checkPendingReservation($cnpj));

        return $alerts;
    }

    /** Verifica se CNPJ já tem responsável em client_wallets. */
    private function checkPortfolio(string $cnpj): array
    {
        try {
            $row = $this->db->table('client_wallets')
                ->select('vendor_id')
                ->where('cnpj', $cnpj)
                ->where('vendor_id IS NOT NULL')
                ->get()->getRowArray();
        } catch (\Throwable) {
            return [];
        }

        if ($row === null) {
            return [];
        }

        return [[
            'type'   => 'portfolio_assigned',
            'label'  => 'CNPJ já possui responsável de carteira',
            'detail' => null,
        ]];
    }

    /** Verifica se há oportunidade aberta para o mesmo CNPJ + campanha. */
    private function checkActiveOpportunity(string $cnpj, int $campaignId, int $excludeOpportunityId): array
    {
        try {
            $builder = $this->db->table('ve_opportunities o')
                ->select('o.id, o.status, e.display_name AS originator_name')
                ->join('employees e', 'e.id = o.originator_employee_id', 'left')
                ->where('o.cnpj', $cnpj)
                ->where('o.campaign_id', $campaignId)
                ->where("o.status NOT IN ('archived','closed')");

            if ($excludeOpportunityId > 0) {
                $builder->where('o.id !=', $excludeOpportunityId);
            }

            $row = $builder->get()->getRowArray();
        } catch (\Throwable) {
            return [];
        }

        if ($row === null) {
            return [];
        }

        return [[
            'type'   => 'active_opportunity',
            'label'  => 'Oportunidade ativa para este CNPJ nesta campanha',
            'detail' => $row['originator_name'] !== null
                ? 'Originada por: ' . $row['originator_name']
                : null,
        ]];
    }

    /** Verifica se há reserva técnica pendente para o CNPJ. */
    private function checkPendingReservation(string $cnpj): array
    {
        try {
            $row = $this->db->table('ve_portfolio_reservations')
                ->select('reservation_id, status')
                ->where('cnpj', $cnpj)
                ->where("status NOT IN ('resolved','cancelled')")
                ->get()->getRowArray();
        } catch (\Throwable) {
            return [];
        }

        if ($row === null) {
            return [];
        }

        return [[
            'type'   => 'pending_reservation',
            'label'  => 'Reserva técnica de carteira pendente para este CNPJ',
            'detail' => 'Reserva ' . $row['reservation_id'] . ' em análise. Comunique ao coordenador antes de prosseguir.',
        ]];
    }
}
