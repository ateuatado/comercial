<?php

namespace App\Controllers;

use App\Models\ClientStatusHistoryModel;
use App\Models\ClientWalletModel;
use App\Models\ProspectingFlagModel;
use App\Models\VendorModel;

/**
 * Controller: Carteira
 * Portal operacional — acessível por acom e gerente_conta.
 */
class CarteiraController extends BaseController
{
    protected VendorModel $vendorModel;
    protected ClientWalletModel $walletModel;
    protected ProspectingFlagModel $flagModel;
    protected ClientStatusHistoryModel $historyModel;

    public function __construct()
    {
        $this->vendorModel  = model(VendorModel::class);
        $this->walletModel  = model(ClientWalletModel::class);
        $this->flagModel    = model(ProspectingFlagModel::class);
        $this->historyModel = model(ClientStatusHistoryModel::class);
    }

    /**
     * Exibe a carteira do usuário autenticado.
     * Carteira isolada por vendor: usuário só vê seus próprios clientes.
     *
     * @return string
     */
    public function index(): string
    {
        $user = auth()->user();

        // 1. Buscar o vendor vinculado ao usuário autenticado
        $vendor = $this->vendorModel->where('user_id', $user->id)->first();
        if (! $vendor) {
            return view('errors/unauthorized', [
                'message' => 'Nenhum vendor vinculado a este usuário.',
            ]);
        }

        // 2. Buscar a carteira completa do vendor com dados da receita e prospecção
        $db = db_connect();
        $clients = $db->table('client_wallets cw')
            ->select([
                'cw.id',
                'cw.cnpj',
                'cw.vendor_id',
                'cw.status_operacional',
                'cw.atribuido_em',
                'cw.updated_at',
                'e.razao_social',
                'e.capital_social',
                'pf.motivo as motivo_suspeita',
                'pf.analisado_em as data_suspeita',
            ])
            ->join('receita.empresas e', 'e.cnpj_basico = cw.cnpj_basico', 'left')
            ->join('prospecting_flags pf', 'pf.cnpj = cw.cnpj AND pf.status = \'pendente\'', 'left')
            ->where('cw.vendor_id', $vendor['id'])
            ->orderBy('cw.atribuido_em', 'DESC')
            ->get()
            ->getResultArray();

        return view('carteira/index', [
            'page_title' => 'Minha Carteira',
            'username'   => $user->username,
            'vendor'     => $vendor,
            'clients'    => $clients,
            'total'      => count($clients),
        ]);
    }

    /**
     * Atualiza o status operacional de um cliente na carteira do ACOM.
     * Valida transições conforme a matriz de permissões para o ACOM/Gerente de Conta.
     *
     * @return \CodeIgniter\HTTP\RedirectResponse
     */
    public function updateStatus()
    {
        $user = auth()->user();

        // 1. Validar se é ACOM ou Gerente de Conta
        if (! $user->inGroup(['acom', 'gerente_conta'])) {
            return redirect()->back()->with('error', 'Acesso não permitido.');
        }

        // 2. Buscar o vendor do usuário
        $vendor = $this->vendorModel->where('user_id', $user->id)->first();
        if (! $vendor) {
            return redirect()->back()->with('error', 'Vendor não encontrado.');
        }

        // 3. Validar requisição
        $cnpj = $this->request->getPost('cnpj');
        $novoStatus = $this->request->getPost('status_novo');

        if (! $cnpj || ! $novoStatus) {
            return redirect()->back()->with('error', 'Parâmetros inválidos.');
        }

        // 4. Buscar cliente na carteira do ACOM (isolamento)
        $cliente = $this->walletModel
            ->where('cnpj', $cnpj)
            ->where('vendor_id', $vendor['id'])
            ->first();

        if (! $cliente) {
            return redirect()->back()->with('error', 'Cliente não encontrado na sua carteira.');
        }

        $statusAtual = $cliente['status_operacional'];

        // 5. Validar transição conforme matriz do ACOM
        if (! $this->isValidTransitionForAcom($statusAtual, $novoStatus)) {
            return redirect()->back()->with(
                'error',
                "Transição não permitida: {$statusAtual} → {$novoStatus}"
            );
        }

        // 6. Atualizar status em client_wallets
        $this->walletModel->update($cliente['id'], [
            'status_operacional' => $novoStatus,
            'updated_at'         => date('Y-m-d H:i:s'),
        ]);

        // 7. Registrar em client_status_history
        $this->historyModel->recordTransition(
            $cnpj,
            $vendor['id'],
            $statusAtual,
            $novoStatus,
            $user->id
        );

        return redirect()->back()->with('message', "Status atualizado: {$statusAtual} → {$novoStatus}");
    }

    /**
     * Valida transição de status para ACOM/Gerente de Conta.
     *
     * Transições permitidas:
     * - novo → em_acompanhamento, sem_contato
     * - em_acompanhamento → sem_contato, convertido
     * - sem_contato → em_acompanhamento
     *
     * @param string $statusAtual
     * @param string $novoStatus
     *
     * @return bool
     */
    private function isValidTransitionForAcom(string $statusAtual, string $novoStatus): bool
    {
        $matrizTransicao = [
            'novo'                => ['em_acompanhamento', 'sem_contato'],
            'em_acompanhamento'   => ['sem_contato', 'convertido'],
            'sem_contato'         => ['em_acompanhamento'],
            'convertido'          => [],
            'bloqueado'           => [],
            'inativo'             => [],
        ];

        $transicoesPerm = $matrizTransicao[$statusAtual] ?? [];

        return in_array($novoStatus, $transicoesPerm, true);
    }
}
