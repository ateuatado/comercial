<?php

namespace App\Controllers;

use App\Models\VendorUserModel;
use App\Models\CarteiraRawModel;
use App\Models\VendorNoteModel;
use App\Models\SegmentServiceModel;
use App\Models\ClientStrategyModel;
use App\Models\ClientLocationModel;

/**
 * Controller: Vendedor
 * Interface mobile-first para o vendedor acessar sua carteira.
 */
class VendedorController extends BaseController
{
    protected VendorUserModel $vendorModel;
    protected ?array $vendorUser = null;

    public function __construct()
    {
        $this->vendorModel = new VendorUserModel();
    }

    /**
     * Carrega o vendor_user do usuário logado.
     */
    private function getVendorUser(): ?array
    {
        if ($this->vendorUser !== null) {
            return $this->vendorUser;
        }
        $user = auth()->user();
        if (!$user) return null;
        $this->vendorUser = $this->vendorModel->findByShieldUserId((int) $user->id);
        return $this->vendorUser;
    }

    // ─── Dashboard ───────────────────────────────────────────────

    public function index()
    {
        $vendorUser = $this->getVendorUser();
        if (!$vendorUser) return redirect()->to('/sem-carteira');

        $db = db_connect();
        $mat = $vendorUser['matricula'];

        $totalClientes = $db->query("SELECT COUNT(*) AS total FROM carteira_raw WHERE matricula_mcmcu = ?", [$mat])->getRow()->total;
        $categorias = $db->query("SELECT categoria, COUNT(*) AS total FROM carteira_raw WHERE matricula_mcmcu = ? GROUP BY categoria ORDER BY total DESC", [$mat])->getResultArray();
        $ciclos = $db->query("SELECT ciclo_de_vida, COUNT(*) AS total FROM carteira_raw WHERE matricula_mcmcu = ? GROUP BY ciclo_de_vida ORDER BY total DESC", [$mat])->getResultArray();
        $segmentos = $db->query("SELECT COUNT(DISTINCT segmento_mercado) AS total FROM carteira_raw WHERE matricula_mcmcu = ?", [$mat])->getRow()->total;

        $noteModel = new VendorNoteModel();
        $ultimasNotas = $noteModel->getRecentByVendor($mat, 5);
        $isCoordenador = $this->vendorModel->isCoordinator($mat);

        return view('vendedor/dashboard', compact('vendorUser', 'totalClientes', 'categorias', 'ciclos', 'segmentos', 'ultimasNotas', 'isCoordenador'));
    }

    // ─── Clientes ────────────────────────────────────────────────

    public function clientesView()
    {
        $vendorUser = $this->getVendorUser();
        if (!$vendorUser) return redirect()->to('/sem-carteira');

        $db = db_connect();
        $mat = $vendorUser['matricula'];

        $categorias = array_column($db->query("SELECT DISTINCT categoria FROM carteira_raw WHERE matricula_mcmcu = ? AND categoria IS NOT NULL ORDER BY categoria", [$mat])->getResultArray(), 'categoria');
        $segmentos  = array_column($db->query("SELECT DISTINCT segmento_mercado FROM carteira_raw WHERE matricula_mcmcu = ? AND segmento_mercado IS NOT NULL ORDER BY segmento_mercado", [$mat])->getResultArray(), 'segmento_mercado');
        $ciclos     = array_column($db->query("SELECT DISTINCT ciclo_de_vida FROM carteira_raw WHERE matricula_mcmcu = ? AND ciclo_de_vida IS NOT NULL ORDER BY ciclo_de_vida", [$mat])->getResultArray(), 'ciclo_de_vida');

        return view('vendedor/clientes', compact('vendorUser', 'categorias', 'segmentos', 'ciclos'));
    }

    public function clientesApi()
    {
        $vendorUser = $this->getVendorUser();
        if (!$vendorUser) return $this->response->setJSON(['error' => 'Sem carteira'])->setStatusCode(403);

        $db = db_connect();
        $filtroCategoria = $this->request->getGet('categoria');
        $filtroSegmento  = $this->request->getGet('segmento');
        $filtroCiclo     = $this->request->getGet('ciclo');
        $busca           = $this->request->getGet('busca');

        $sql = "SELECT c.cnpj, c.razao_social, c.categoria, c.segmento_cliente, c.segmento_mercado,
                       c.ciclo_de_vida, c.cnae, c.cnae_desc, c.canais_vendas, c.conta_numero, c.conta_nome,
                       c.prospeccao, c.nat_juridica, c.se, c.gerencia, c.grupo_cliente, e.capital_social
                FROM carteira_raw c
                LEFT JOIN receita.empresas e ON e.cnpj_basico = SUBSTRING(c.cnpj, 1, 8)
                WHERE c.matricula_mcmcu = ?";
        $params = [$vendorUser['matricula']];

        if (!empty($filtroCategoria)) { $sql .= " AND c.categoria = ?"; $params[] = $filtroCategoria; }
        if (!empty($filtroSegmento))  { $sql .= " AND c.segmento_mercado = ?"; $params[] = $filtroSegmento; }
        if (!empty($filtroCiclo))     { $sql .= " AND c.ciclo_de_vida = ?"; $params[] = $filtroCiclo; }
        if (!empty($busca))           { $sql .= " AND (c.cnpj LIKE ? OR LOWER(c.razao_social) LIKE LOWER(?))"; $params[] = "%{$busca}%"; $params[] = "%{$busca}%"; }

        $sql .= " ORDER BY c.razao_social ASC";
        $clientes = $db->query($sql, $params)->getResultArray();

        return $this->response->setJSON(['total' => count($clientes), 'clientes' => $clientes]);
    }

    // ─── Detalhe do Cliente ──────────────────────────────────────

    public function clienteDetalhe(string $cnpj)
    {
        $vendorUser = $this->getVendorUser();
        if (!$vendorUser) return redirect()->to('/sem-carteira');

        $db = db_connect();
        $cliente = $db->query("SELECT * FROM carteira_raw WHERE cnpj = ? AND matricula_mcmcu = ? LIMIT 1", [$cnpj, $vendorUser['matricula']])->getRowArray();
        if (!$cliente) return redirect()->to('/vendedor')->with('error', 'Cliente não encontrado na sua carteira.');

        $noteModel = new VendorNoteModel();
        $notas = $noteModel->getByClientAndVendor($cnpj, $vendorUser['matricula']);

        $strategyModel = new ClientStrategyModel();
        $estrategias = $strategyModel->getByClient($cnpj, $vendorUser['matricula']);

        return view('vendedor/cliente_detalhe', compact('vendorUser', 'cliente', 'notas', 'estrategias'));
    }

    // ─── Formulário de Nota ──────────────────────────────────────

    public function notaForm(string $cnpj)
    {
        $vendorUser = $this->getVendorUser();
        if (!$vendorUser) return redirect()->to('/sem-carteira');

        $db = db_connect();
        $cliente = $db->query("SELECT cnpj, razao_social, categoria, segmento_mercado FROM carteira_raw WHERE cnpj = ? AND matricula_mcmcu = ? LIMIT 1", [$cnpj, $vendorUser['matricula']])->getRowArray();
        if (!$cliente) return redirect()->to('/vendedor')->with('error', 'Cliente não encontrado.');

        return view('vendedor/nota_form', compact('vendorUser', 'cliente'));
    }

    /**
     * POST — Grava nota no banco.
     */
    public function notaSalvar()
    {
        $vendorUser = $this->getVendorUser();
        if (!$vendorUser) return $this->response->setJSON(['error' => 'Sem carteira'])->setStatusCode(403);

        $cnpj       = $this->request->getPost('cnpj');
        $tipo       = $this->request->getPost('tipo');
        $conteudo   = $this->request->getPost('conteudo');
        $sentimento = $this->request->getPost('sentimento');

        if (empty($cnpj) || empty($tipo) || empty($conteudo)) {
            return $this->response->setJSON(['error' => 'Campos obrigatórios não preenchidos.'])->setStatusCode(422);
        }

        // Verifica se o CNPJ pertence à carteira do vendedor
        $db = db_connect();
        $exists = $db->query("SELECT 1 FROM carteira_raw WHERE cnpj = ? AND matricula_mcmcu = ? LIMIT 1", [$cnpj, $vendorUser['matricula']])->getRow();
        if (!$exists) {
            return $this->response->setJSON(['error' => 'Cliente não pertence à sua carteira.'])->setStatusCode(403);
        }

        $noteModel = new VendorNoteModel();
        $noteModel->insert([
            'matricula_vendedor' => $vendorUser['matricula'],
            'cnpj'               => $cnpj,
            'tipo'               => $tipo,
            'conteudo'           => $conteudo,
            'sentimento'         => $sentimento ?: null,
            'created_at'         => date('Y-m-d H:i:s'),
        ]);

        return $this->response->setJSON(['success' => true, 'message' => 'Nota registrada com sucesso.']);
    }

    // ─── Estratégias ─────────────────────────────────────────────

    /**
     * GET — Retorna serviços disponíveis para o segmento.
     */
    public function servicosSegmento(string $segmento)
    {
        $model = new SegmentServiceModel();
        $servicos = $model->getBySegment($segmento);
        return $this->response->setJSON($servicos);
    }

    /**
     * POST — Salva estratégia (composição de blocos de serviço) para um cliente.
     */
    public function estrategiaSalvar()
    {
        $vendorUser = $this->getVendorUser();
        if (!$vendorUser) return $this->response->setJSON(['error' => 'Sem carteira'])->setStatusCode(403);

        $cnpj      = $this->request->getPost('cnpj');
        $serviceIds = $this->request->getPost('service_ids'); // array de IDs

        if (empty($cnpj) || empty($serviceIds) || !is_array($serviceIds)) {
            return $this->response->setJSON(['error' => 'Dados incompletos.'])->setStatusCode(422);
        }

        $strategyModel = new ClientStrategyModel();

        // Limpa estratégias anteriores e recria
        $strategyModel->clearForClient($cnpj, $vendorUser['matricula']);

        foreach ($serviceIds as $serviceId) {
            $strategyModel->insert([
                'matricula_vendedor' => $vendorUser['matricula'],
                'cnpj'               => $cnpj,
                'service_id'         => (int) $serviceId,
                'created_at'         => date('Y-m-d H:i:s'),
            ]);
        }

        return $this->response->setJSON(['success' => true, 'message' => 'Estratégia salva.']);
    }
}
