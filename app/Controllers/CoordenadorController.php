<?php

namespace App\Controllers;

use App\Models\VendorUserModel;
use App\Models\VendorMovementModel;

/**
 * Controller: Coordenador — Fase 3
 *
 * Gestão do time pelo coordenador, limitada à própria gerência.
 * Funcionalidades:
 *   - Visão do time (Fase 2.9 — mantida)
 *   - CRUD de vendedores (Fase 3.1)
 *   - Clientes livres + atribuição (Fase 3.2)
 *   - Transferência de clientes entre vendedores (Fase 3.3)
 *   - Transferência de vendedor para outro coordenador (Fase 3.4)
 */
class CoordenadorController extends BaseController
{
    protected VendorUserModel    $vendorModel;
    protected VendorMovementModel $movementModel;

    public function __construct()
    {
        $this->vendorModel   = new VendorUserModel();
        $this->movementModel = new VendorMovementModel();
    }

    // =========================================================================
    // HELPERS INTERNOS
    // =========================================================================

    /** Retorna o vendor_user do coordenador logado, ou null. */
    private function getLoggedVendor(): ?array
    {
        $user = auth()->user();
        if (!$user) return null;
        return $this->vendorModel->findByShieldUserId((int) $user->id);
    }

    /**
     * Retorna o coordenador logado já validado como coordenador.
     * Redireciona se não for coordenador.
     */
    private function requireCoordenador(): array|false
    {
        $v = $this->getLoggedVendor();
        if (!$v) {
            redirect()->to('/sem-carteira')->send();
            return false;
        }
        if (!$this->vendorModel->isCoordinator($v['matricula'])) {
            redirect()->to('/vendedor')->with('error', 'Acesso restrito a coordenadores.')->send();
            return false;
        }
        return $v;
    }

    /**
     * Verifica se um vendedor pertence ao time E gerência do coordenador logado.
     * Retorna o vendedor ou lança redirect.
     */
    private function requireDoTime(string $matriculaAlvo, array $coordenador): array|false
    {
        $alvo = $this->vendorModel->findByMatricula($matriculaAlvo);

        if (!$alvo) {
            session()->setFlashdata('error', 'Vendedor não encontrado.');
            redirect()->to('/coordenador')->send();
            return false;
        }

        // Deve ser do time (mtr_coordenador) E mesma gerência
        $mesmoTime    = ($alvo['mtr_coordenador'] ?? '') === $coordenador['matricula'];
        $mesmaGerencia = ($alvo['gerencia'] ?? '') === ($coordenador['gerencia'] ?? '');

        if (!$mesmoTime || !$mesmaGerencia) {
            session()->setFlashdata('error', 'Vendedor não pertence ao seu time.');
            redirect()->to('/coordenador')->send();
            return false;
        }

        return $alvo;
    }

    // =========================================================================
    // FASE 2.9 — VISÃO DO TIME (mantida e aprimorada)
    // =========================================================================

    public function index()
    {
        $coord = $this->requireCoordenador();
        if (!$coord) return;

        $matricula  = $coord['matricula'];
        $vendedores = $this->vendorModel->getByCoordinator($matricula);

        $db = db_connect();
        foreach ($vendedores as &$v) {
            $v['total_clientes'] = (int) $db->query(
                "SELECT COUNT(*) as n FROM carteira_raw WHERE matricula_mcmcu = ?",
                [$v['matricula']]
            )->getRow()->n;

            $v['categorias'] = $db->query(
                "SELECT categoria, COUNT(*) as total FROM carteira_raw WHERE matricula_mcmcu = ? GROUP BY categoria ORDER BY total DESC",
                [$v['matricula']]
            )->getResultArray();
        }
        unset($v);

        return view('coordenador/index', [
            'vendorUser'        => $coord,
            'vendedores'        => $vendedores,
            'totalVendedores'   => count($vendedores),
            'totalClientesTime' => array_sum(array_column($vendedores, 'total_clientes')),
        ]);
    }

    public function vendedorDetalhe(string $matricula)
    {
        $coord   = $this->requireCoordenador();
        if (!$coord) return;
        $vendedor = $this->requireDoTime($matricula, $coord);
        if (!$vendedor) return;

        $db = db_connect();
        return view('coordenador/vendedor_detalhe', [
            'vendorUser'    => $coord,
            'vendedor'      => $vendedor,
            'totalClientes' => (int) $db->query("SELECT COUNT(*) as n FROM carteira_raw WHERE matricula_mcmcu = ?", [$matricula])->getRow()->n,
            'categorias'    => $db->query("SELECT categoria, COUNT(*) as total FROM carteira_raw WHERE matricula_mcmcu = ? GROUP BY categoria ORDER BY total DESC", [$matricula])->getResultArray(),
            'ciclos'        => $db->query("SELECT ciclo_de_vida, COUNT(*) as total FROM carteira_raw WHERE matricula_mcmcu = ? GROUP BY ciclo_de_vida ORDER BY total DESC", [$matricula])->getResultArray(),
        ]);
    }

    public function vendedorClientes(string $matricula)
    {
        $coord   = $this->requireCoordenador();
        if (!$coord) return;
        $vendedor = $this->requireDoTime($matricula, $coord);
        if (!$vendedor) return;

        $db      = db_connect();
        $clientes = $db->query(
            "SELECT cnpj, razao_social, categoria, segmento_mercado, ciclo_de_vida FROM carteira_raw WHERE matricula_mcmcu = ? ORDER BY razao_social",
            [$matricula]
        )->getResultArray();

        // Vendedores do time para o modal de transferência
        $time = array_filter(
            $this->vendorModel->getByCoordinator($coord['matricula']),
            fn($v) => $v['matricula'] !== $matricula && $v['ativo']
        );

        return view('coordenador/vendedor_clientes', [
            'vendorUser' => $coord,
            'vendedor'   => $vendedor,
            'clientes'   => $clientes,
            'time'       => array_values($time),
        ]);
    }

    // =========================================================================
    // FASE 3.1 — CRUD DE VENDEDORES
    // =========================================================================

    /** GET /coordenador/vendedores/novo */
    public function novoVendedor()
    {
        $coord = $this->requireCoordenador();
        if (!$coord) return;

        return view('coordenador/vendedor_form', [
            'vendorUser' => $coord,
            'vendedor'   => null,
            'perfis'     => ['ACOM', 'GC', 'CEM', 'AGF', 'CAC', 'ACOM I', 'ACOM II', 'ACOM III'],
            'titulo'     => 'Novo Vendedor',
            'action'     => site_url('coordenador/vendedores/salvar'),
        ]);
    }

    /** POST /coordenador/vendedores/salvar */
    public function salvarVendedor()
    {
        $coord = $this->requireCoordenador();
        if (!$coord) return;

        $matricula = trim($this->request->getPost('matricula'));
        $nome      = trim($this->request->getPost('nome'));
        $perfil    = trim($this->request->getPost('perfil_vendedor'));
        $email     = trim($this->request->getPost('email') ?? '');

        // Validação básica
        if (!$matricula || !$nome || !$perfil) {
            return redirect()->back()->withInput()
                ->with('error', 'Matrícula, nome e perfil são obrigatórios.');
        }

        // Matrícula única
        if ($this->vendorModel->findByMatricula($matricula)) {
            return redirect()->back()->withInput()
                ->with('error', "Matrícula {$matricula} já está cadastrada no sistema.");
        }

        $this->vendorModel->insert([
            'matricula'        => $matricula,
            'nome'             => $nome,
            'email'            => $email ?: null,
            'perfil_vendedor'  => $perfil,
            'se'               => $coord['se'],
            'gerencia'         => $coord['gerencia'],
            'mtr_coordenador'  => $coord['matricula'],
            'nome_coordenador' => $coord['nome'],
            'gerencia_vendas'  => $coord['gerencia_vendas'] ?? null,
            'ativo'            => true,
        ]);

        return redirect()->to('/coordenador')
            ->with('success', "Vendedor {$nome} ({$matricula}) cadastrado com sucesso.");
    }

    /** GET /coordenador/vendedor/:m/editar */
    public function editarVendedor(string $matricula)
    {
        $coord   = $this->requireCoordenador();
        if (!$coord) return;
        $vendedor = $this->requireDoTime($matricula, $coord);
        if (!$vendedor) return;

        return view('coordenador/vendedor_form', [
            'vendorUser' => $coord,
            'vendedor'   => $vendedor,
            'perfis'     => ['ACOM', 'GC', 'CEM', 'AGF', 'CAC', 'ACOM I', 'ACOM II', 'ACOM III'],
            'titulo'     => 'Editar Vendedor',
            'action'     => site_url("coordenador/vendedor/{$matricula}/atualizar"),
        ]);
    }

    /** POST /coordenador/vendedor/:m/atualizar */
    public function atualizarVendedor(string $matricula)
    {
        $coord   = $this->requireCoordenador();
        if (!$coord) return;
        $vendedor = $this->requireDoTime($matricula, $coord);
        if (!$vendedor) return;

        $nome   = trim($this->request->getPost('nome'));
        $perfil = trim($this->request->getPost('perfil_vendedor'));
        $email  = trim($this->request->getPost('email') ?? '');

        if (!$nome || !$perfil) {
            return redirect()->back()->withInput()
                ->with('error', 'Nome e perfil são obrigatórios.');
        }

        $this->vendorModel->where('matricula', $matricula)->set([
            'nome'            => $nome,
            'perfil_vendedor' => $perfil,
            'email'           => $email ?: null,
            'updated_at'      => date('Y-m-d H:i:s'),
        ])->update();

        return redirect()->to("/coordenador/vendedor/{$matricula}")
            ->with('success', 'Dados do vendedor atualizados.');
    }

    /** POST /coordenador/vendedor/:m/desativar */
    public function desativarVendedor(string $matricula)
    {
        $coord   = $this->requireCoordenador();
        if (!$coord) return;
        $vendedor = $this->requireDoTime($matricula, $coord);
        if (!$vendedor) return;

        $this->vendorModel->where('matricula', $matricula)->set([
            'ativo'      => false,
            'updated_at' => date('Y-m-d H:i:s'),
        ])->update();

        return redirect()->to('/coordenador')
            ->with('success', "Vendedor {$vendedor['nome']} desativado. Clientes ficam sem dono até redistribuição.");
    }

    // =========================================================================
    // FASE 3.2 — CLIENTES LIVRES + ATRIBUIÇÃO
    // =========================================================================

    /** GET /coordenador/clientes-livres */
    public function clientesLivres()
    {
        $coord = $this->requireCoordenador();
        if (!$coord) return;

        $db      = db_connect();
        $gerencia = $coord['gerencia'] ?? '';

        // CNPJs na carteira_raw da gerência do coordenador que NÃO têm vínculo ativo em client_wallets
        $busca = trim($this->request->getGet('q') ?? '');
        $params = [$gerencia];

        $where = '';
        if ($busca) {
            $where  = " AND (cr.cnpj ILIKE ? OR cr.razao_social ILIKE ?)";
            $params[] = "%{$busca}%";
            $params[] = "%{$busca}%";
        }

        $clientes = $db->query("
            SELECT cr.cnpj, cr.razao_social, cr.categoria, cr.segmento_mercado, cr.ciclo_de_vida
            FROM carteira_raw cr
            WHERE cr.gerencia = ?
              AND cr.cnpj NOT IN (
                  SELECT cw.cnpj FROM client_wallets cw
                  WHERE cw.vendor_id IS NOT NULL
              )
            {$where}
            ORDER BY cr.razao_social
            LIMIT 200
        ", $params)->getResultArray();

        $time = $this->vendorModel->getByCoordinator($coord['matricula']);
        $time = array_filter($time, fn($v) => $v['ativo']);

        return view('coordenador/clientes_livres', [
            'vendorUser' => $coord,
            'clientes'   => $clientes,
            'time'       => array_values($time),
            'busca'      => $busca,
        ]);
    }

    /** POST /coordenador/clientes-livres/atribuir */
    public function atribuirClientes()
    {
        $coord = $this->requireCoordenador();
        if (!$coord) return;

        $cnpjs           = $this->request->getPost('cnpjs') ?? [];
        $matriculaDestino = trim($this->request->getPost('matricula_destino') ?? '');

        if (empty($cnpjs) || !$matriculaDestino) {
            return redirect()->back()->with('error', 'Selecione pelo menos um cliente e um vendedor destino.');
        }

        $destino = $this->requireDoTime($matriculaDestino, $coord);
        if (!$destino) return;

        $db       = db_connect();
        $vendorId = $destino['id'] ?? null;

        // Busca ou cria o id numérico do vendedor em vendors (FK de client_wallets)
        // client_wallets usa vendor_id da tabela vendors — precisamos do id legado
        $vendorRow = $db->query(
            "SELECT id FROM vendors WHERE matricula = ? LIMIT 1",
            [$matriculaDestino]
        )->getRow();

        if (!$vendorRow) {
            return redirect()->back()->with('error', 'Vendedor destino não encontrado na tabela de vínculos.');
        }

        $agora    = date('Y-m-d H:i:s');
        $inserted = 0;

        foreach ($cnpjs as $cnpj) {
            $cnpj = preg_replace('/\D/', '', $cnpj);
            if (strlen($cnpj) !== 14) continue;

            // Upsert: insert se não existe, update se existe sem dono
            $existing = $db->query("SELECT id, vendor_id FROM client_wallets WHERE cnpj = ?", [$cnpj])->getRow();

            if (!$existing) {
                $db->query(
                    "INSERT INTO client_wallets (cnpj, vendor_id, status_operacional, origem_atribuicao, atribuido_em, created_at, updated_at)
                     VALUES (?, ?, 'ativo', 'coordenador', ?, ?, ?)",
                    [$cnpj, $vendorRow->id, $agora, $agora, $agora]
                );
            } elseif (!$existing->vendor_id) {
                $db->query(
                    "UPDATE client_wallets SET vendor_id = ?, origem_atribuicao = 'coordenador', atribuido_em = ?, updated_at = ? WHERE cnpj = ?",
                    [$vendorRow->id, $agora, $agora, $cnpj]
                );
            } else {
                continue; // já tem dono — pula
            }

            // Auditoria em wallet_movements
            $db->query(
                "INSERT INTO wallet_movements (cnpj, vendor_id_anterior, vendor_id_novo, tipo_movimento, realizado_por, motivo, realizado_por_perfil, created_at)
                 VALUES (?, NULL, ?, 'manual', ?, 'Atribuição pelo coordenador', 'coordenador', ?)",
                [$cnpj, $vendorRow->id, auth()->user()->id, $agora]
            );
            $inserted++;
        }

        return redirect()->to('/coordenador/clientes-livres')
            ->with('success', "{$inserted} cliente(s) atribuído(s) a {$destino['nome']}.");
    }

    // =========================================================================
    // FASE 3.3 — TRANSFERÊNCIA DE CLIENTES ENTRE VENDEDORES
    // =========================================================================

    /** POST /coordenador/vendedor/:m/transferir-clientes */
    public function processarTransferenciaClientes(string $matriculaOrigem)
    {
        $coord  = $this->requireCoordenador();
        if (!$coord) return;
        $origem = $this->requireDoTime($matriculaOrigem, $coord);
        if (!$origem) return;

        $cnpjs            = $this->request->getPost('cnpjs') ?? [];
        $matriculaDestino = trim($this->request->getPost('matricula_destino') ?? '');
        $motivo           = trim($this->request->getPost('motivo') ?? '');

        if (empty($cnpjs)) {
            return redirect()->back()->with('error', 'Selecione pelo menos um cliente para transferir.');
        }
        if (!$matriculaDestino) {
            return redirect()->back()->with('error', 'Selecione o vendedor destino.');
        }
        if (!$motivo) {
            return redirect()->back()->with('error', 'O motivo da transferência é obrigatório.');
        }
        if ($matriculaDestino === $matriculaOrigem) {
            return redirect()->back()->with('error', 'Vendedor destino deve ser diferente do de origem.');
        }

        $destino = $this->requireDoTime($matriculaDestino, $coord);
        if (!$destino) return;

        $db = db_connect();

        // Busca IDs na tabela vendors
        $vendorOrigem  = $db->query("SELECT id FROM vendors WHERE matricula = ? LIMIT 1", [$matriculaOrigem])->getRow();
        $vendorDestino = $db->query("SELECT id FROM vendors WHERE matricula = ? LIMIT 1", [$matriculaDestino])->getRow();

        if (!$vendorOrigem || !$vendorDestino) {
            return redirect()->back()->with('error', 'Problema ao identificar os vendedores no sistema legado.');
        }

        $agora      = date('Y-m-d H:i:s');
        $transferidos = 0;

        foreach ($cnpjs as $cnpj) {
            $cnpj = preg_replace('/\D/', '', $cnpj);
            if (strlen($cnpj) !== 14) continue;

            // Verifica que o cliente pertence à origem
            $wallet = $db->query(
                "SELECT id FROM client_wallets WHERE cnpj = ? AND vendor_id = ?",
                [$cnpj, $vendorOrigem->id]
            )->getRow();

            if (!$wallet) continue; // não pertence à origem — pula

            $db->query(
                "UPDATE client_wallets SET vendor_id = ?, origem_atribuicao = 'coordenador', atribuido_em = ?, updated_at = ? WHERE cnpj = ?",
                [$vendorDestino->id, $agora, $agora, $cnpj]
            );

            $db->query(
                "INSERT INTO wallet_movements (cnpj, vendor_id_anterior, vendor_id_novo, tipo_movimento, realizado_por, motivo, realizado_por_perfil, created_at)
                 VALUES (?, ?, ?, 'manual', ?, ?, 'coordenador', ?)",
                [$cnpj, $vendorOrigem->id, $vendorDestino->id, auth()->user()->id, $motivo, $agora]
            );
            $transferidos++;
        }

        return redirect()->to("/coordenador/vendedor/{$matriculaOrigem}/clientes")
            ->with('success', "{$transferidos} cliente(s) transferido(s) para {$destino['nome']}.");
    }

    // =========================================================================
    // FASE 3.4 — TRANSFERÊNCIA DE VENDEDOR ENTRE COORDENADORES
    // =========================================================================

    /** GET /coordenador/vendedor/:m/transferir */
    public function formTransferirVendedor(string $matricula)
    {
        $coord   = $this->requireCoordenador();
        if (!$coord) return;
        $vendedor = $this->requireDoTime($matricula, $coord);
        if (!$vendedor) return;

        // Coordenadores ativos da mesma gerência (excluindo o logado)
        $db          = db_connect();
        $gerencia    = $coord['gerencia'] ?? '';
        $coordenadores = $db->query("
            SELECT DISTINCT mtr_coordenador AS matricula, nome_coordenador AS nome
            FROM vendor_users
            WHERE gerencia = ?
              AND mtr_coordenador IS NOT NULL
              AND mtr_coordenador <> ?
              AND ativo = true
            ORDER BY nome_coordenador
        ", [$gerencia, $coord['matricula']])->getResultArray();

        return view('coordenador/transferir_vendedor', [
            'vendorUser'   => $coord,
            'vendedor'     => $vendedor,
            'coordenadores' => $coordenadores,
        ]);
    }

    /** POST /coordenador/vendedor/:m/transferir */
    public function processarTransferenciaVendedor(string $matricula)
    {
        $coord   = $this->requireCoordenador();
        if (!$coord) return;
        $vendedor = $this->requireDoTime($matricula, $coord);
        if (!$vendedor) return;

        $mtrDestino = trim($this->request->getPost('coord_destino') ?? '');
        $motivo     = trim($this->request->getPost('motivo') ?? '');

        if (!$mtrDestino || !$motivo) {
            return redirect()->back()->withInput()
                ->with('error', 'Coordenador destino e motivo são obrigatórios.');
        }

        // Valida que o coordenador destino existe e é da mesma gerência
        $db           = db_connect();
        $coordDestino = $this->vendorModel->findByMatricula($mtrDestino);

        if (!$coordDestino) {
            return redirect()->back()->with('error', 'Coordenador destino não encontrado.');
        }

        if (($coordDestino['gerencia'] ?? '') !== ($coord['gerencia'] ?? '')) {
            return redirect()->back()->with('error', 'O coordenador destino deve ser da mesma gerência.');
        }

        // Atualiza o vínculo hierárquico do vendedor
        $this->vendorModel->where('matricula', $matricula)->set([
            'mtr_coordenador'  => $mtrDestino,
            'nome_coordenador' => $coordDestino['nome'],
            'updated_at'       => date('Y-m-d H:i:s'),
        ])->update();

        // Registra na auditoria
        $this->movementModel->registrar(
            matricula:          $matricula,
            coordOrigem:        $coord['matricula'],
            nomeCoordOrigem:    $coord['nome'],
            coordDestino:       $mtrDestino,
            nomeCoordDestino:   $coordDestino['nome'],
            gerencia:           $coord['gerencia'] ?? '',
            se:                 $coord['se'] ?? null,
            motivo:             $motivo,
            feitoPor:           $coord['matricula'],
            feitoPorPerfil:     'coordenador'
        );

        return redirect()->to('/coordenador')
            ->with('success', "{$vendedor['nome']} transferido para a coordenação de {$coordDestino['nome']}.");
    }
}
