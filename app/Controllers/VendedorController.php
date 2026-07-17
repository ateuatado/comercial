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

        // Verificar e auto-prospectar (vincular na carteira) se o cliente não estiver vinculado a este vendedor
        $existsRaw = $db->table('carteira_raw')
                        ->where('cnpj', $cnpj)
                        ->where('matricula_mcmcu', $vendorUser['matricula'])
                        ->get()
                        ->getRow();

        if (!$existsRaw) {
            $est = $db->query("
                SELECT e.*, emp.razao_social
                FROM receita.estabelecimentos e
                LEFT JOIN receita.empresas emp ON e.cnpj_basico = emp.cnpj_basico
                WHERE (e.cnpj_basico || e.cnpj_ordem || e.cnpj_dv) = ?
                LIMIT 1
            ", [$cnpj])->getRowArray();

            if ($est) {
                // Insere na carteira_raw do vendedor
                $db->table('carteira_raw')->insert([
                    'se'                 => $est['uf'] === 'SP' ? 'SPM' : 'SPI',
                    'categoria'          => 'BRONZE',
                    'cnpj'               => $cnpj,
                    'razao_social'       => $est['razao_social'] ?? 'PROSPECTO',
                    'matricula_mcmcu'    => $vendorUser['matricula'],
                    'forca_vendas_nome'  => $vendorUser['nome'] ?? $vendorUser['username'],
                    'ciclo_de_vida'      => 'Ativo',
                    'cnae'               => $est['cnae_fiscal_principal'] ?? null,
                    'created_at'         => date('Y-m-d H:i:s'),
                ]);

                // Garante que existe ou atualiza na client_wallets
                $existsWallet = $db->table('client_wallets')->where('cnpj', $cnpj)->get()->getRow();
                if (!$existsWallet) {
                    $db->table('client_wallets')->insert([
                        'cnpj'               => $cnpj,
                        'vendor_id'          => $vendorUser['id'],
                        'status_operacional' => 'novo',
                        'created_at'         => date('Y-m-d H:i:s'),
                        'updated_at'         => date('Y-m-d H:i:s'),
                    ]);
                } else {
                    $db->table('client_wallets')->where('cnpj', $cnpj)->update([
                        'vendor_id'          => $vendorUser['id'],
                        'updated_at'         => date('Y-m-d H:i:s'),
                    ]);
                }
            }
        }

        $cliente = $db->query("
            SELECT c.*, 
                   cw.rfb_situacao_cadastral, cw.rfb_verificado_em,
                   loc.latitude AS loc_lat, loc.longitude AS loc_lng,
                   e.tipo_logradouro, e.logradouro, e.numero, e.complemento, e.bairro, e.cep, e.uf,
                   m.descricao AS municipio_nome,
                   e.ddd_1, e.telefone_1, e.ddd_2, e.telefone_2,
                   e.email
            FROM carteira_raw c
            LEFT JOIN client_wallets cw ON cw.cnpj = c.cnpj
            LEFT JOIN client_locations loc ON loc.cnpj = c.cnpj
            LEFT JOIN receita.estabelecimentos e ON (e.cnpj_basico || e.cnpj_ordem || e.cnpj_dv) = c.cnpj
            LEFT JOIN receita.municipios m ON e.municipio = m.codigo
            WHERE c.cnpj = ? AND c.matricula_mcmcu = ? 
            LIMIT 1
        ", [$cnpj, $vendorUser['matricula']])->getRowArray();
        if (!$cliente) return redirect()->to('/vendedor')->with('error', 'Cliente não encontrado na sua carteira.');

        $noteModel = new VendorNoteModel();
        $notas = $noteModel->getByClientAndVendor($cnpj, $vendorUser['matricula']);

        $strategyModel = new ClientStrategyModel();
        $estrategias = $strategyModel->getByClient($cnpj, $vendorUser['matricula']);

        // Carrega redes sociais vinculadas
        $redesSociais = $db->table('client_social_media')
                           ->where('cnpj', $cnpj)
                           ->where('status !=', 'rejeitado')
                           ->orderBy('status', 'DESC') // 'validado' primeiro, depois 'sugestao'
                           ->get()
                           ->getResultArray();

        return view('vendedor/cliente_detalhe', compact('vendorUser', 'cliente', 'notas', 'estrategias', 'redesSociais'));
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

    // ─── Geolocalização e Prospecção (Fase 2.6b) ─────────────────

    public function prospectarView()
    {
        $vendorUser = $this->getVendorUser();
        if (!$vendorUser) return redirect()->to('/sem-carteira');

        return view('vendedor/prospectar', compact('vendorUser'));
    }

    public function prospeccaoPesquisaView()
    {
        $vendorUser = $this->getVendorUser();
        if (!$vendorUser) return redirect()->to('/sem-carteira');

        return view('vendedor/prospeccao_pesquisa', compact('vendorUser'));
    }

    public function prospeccaoBuscarApi()
    {
        $vendorUser = $this->getVendorUser();
        if (!$vendorUser) {
            return $this->response->setJSON(['error' => 'Não autorizado'])->setStatusCode(403);
        }

        $searchTerm = strtolower(trim($this->request->getGet('q') ?? ''));
        if (strlen($searchTerm) < 3) {
            return $this->response->setJSON(['success' => true, 'resultados' => []]);
        }

        $db = db_connect();

        $cleanCnpj = preg_replace('/[^0-9]/', '', $searchTerm);
        if (strlen($cleanCnpj) >= 8 && is_numeric($cleanCnpj)) {
            $query = "
                SELECT (e.cnpj_basico || e.cnpj_ordem || e.cnpj_dv) AS cnpj,
                       emp.razao_social, e.nome_fantasia,
                       e.tipo_logradouro, e.logradouro, e.numero, e.complemento, e.bairro, e.cep, e.uf,
                       m.descricao AS municipio_nome,
                       loc.latitude AS loc_lat, loc.longitude AS loc_lng,
                       cw.rfb_situacao_cadastral, cw.rfb_verificado_em
                FROM receita.estabelecimentos e
                LEFT JOIN receita.empresas emp ON e.cnpj_basico = emp.cnpj_basico
                LEFT JOIN receita.municipios m ON e.municipio = m.codigo
                LEFT JOIN client_locations loc ON loc.cnpj = (e.cnpj_basico || e.cnpj_ordem || e.cnpj_dv)
                LEFT JOIN client_wallets cw ON cw.cnpj = (e.cnpj_basico || e.cnpj_ordem || e.cnpj_dv)
                WHERE (e.cnpj_basico || e.cnpj_ordem || e.cnpj_dv) LIKE ?
                LIMIT 30
            ";
            $resultados = $db->query($query, ['%' . $cleanCnpj . '%'])->getResultArray();
        } else {
            $query = "
                SELECT (e.cnpj_basico || e.cnpj_ordem || e.cnpj_dv) AS cnpj,
                       emp.razao_social, e.nome_fantasia,
                       e.tipo_logradouro, e.logradouro, e.numero, e.complemento, e.bairro, e.cep, e.uf,
                       m.descricao AS municipio_nome,
                       loc.latitude AS loc_lat, loc.longitude AS loc_lng,
                       cw.rfb_situacao_cadastral, cw.rfb_verificado_em
                FROM receita.estabelecimentos e
                LEFT JOIN receita.empresas emp ON e.cnpj_basico = emp.cnpj_basico
                LEFT JOIN receita.municipios m ON e.municipio = m.codigo
                LEFT JOIN client_locations loc ON loc.cnpj = (e.cnpj_basico || e.cnpj_ordem || e.cnpj_dv)
                LEFT JOIN client_wallets cw ON cw.cnpj = (e.cnpj_basico || e.cnpj_ordem || e.cnpj_dv)
                WHERE LOWER(emp.razao_social) LIKE ?
                   OR LOWER(e.nome_fantasia) LIKE ?
                   OR LOWER(e.logradouro) LIKE ?
                   OR LOWER(e.bairro) LIKE ?
                LIMIT 30
            ";
            $param = '%' . $searchTerm . '%';
            $resultados = $db->query($query, [$param, $param, $param, $param])->getResultArray();
        }

        foreach ($resultados as &$res) {
            $endParts = [];
            if (!empty($res['tipo_logradouro'])) $endParts[] = trim($res['tipo_logradouro']);
            if (!empty($res['logradouro'])) $endParts[] = trim($res['logradouro']);
            if (!empty($res['numero'])) $endParts[] = 'Nº ' . trim($res['numero']);
            if (!empty($res['complemento'])) $endParts[] = trim($res['complemento']);
            if (!empty($res['bairro'])) $endParts[] = trim($res['bairro']);
            if (!empty($res['municipio_nome'])) $endParts[] = trim($res['municipio_nome']);
            if (!empty($res['uf'])) $endParts[] = trim($res['uf']);
            if (!empty($res['cep'])) $endParts[] = 'CEP ' . trim($res['cep']);

            $res['endereco_completo'] = implode(', ', $endParts);
            $res['rfb_verificado_em_fmt'] = !empty($res['rfb_verificado_em']) ? date('d/m/Y H:i', strtotime($res['rfb_verificado_em'])) : null;
        }

        return $this->response->setJSON([
            'success'    => true,
            'resultados' => $resultados
        ]);
    }

    public function mockGoogleMaps()
    {
        $vendorUser = $this->getVendorUser();
        if (!$vendorUser) return redirect()->to('/sem-carteira');

        return view('vendedor/mock_maps', compact('vendorUser'));
    }

    public function prospectarApi()
    {
        $vendorUser = $this->getVendorUser();
        if (!$vendorUser) return $this->response->setJSON(['error' => 'Sem carteira'])->setStatusCode(403);

        $lat = (float) $this->request->getGet('lat');
        $lng = (float) $this->request->getGet('lng');

        if (empty($lat) || empty($lng)) {
            return $this->response->setJSON(['error' => 'Coordenadas GPS não informadas.'])->setStatusCode(422);
        }

        $db = db_connect();
        $locationModel = new ClientLocationModel();

        // Obtém todas as localizações salvas
        $locations = $locationModel->findAll();
        $proximas = [];

        foreach ($locations as $loc) {
            $dist = ClientLocationModel::haversineDistance($lat, $lng, (float)$loc['latitude'], (float)$loc['longitude']);
            
            // Raio padrão de busca: 10km
            if ($dist <= 10.0) {
                // Descobre se a empresa já está associada a algum vendedor na carteira_raw
                $carteira = $db->table('carteira_raw')
                              ->select('razao_social, matricula_mcmcu')
                              ->where('cnpj', $loc['cnpj'])
                              ->get()
                              ->getRowArray();

                $status = 'Livre';
                if ($carteira) {
                    $status = 'Ocupado'; // Pertence a alguma carteira
                }

                // Carrega dados adicionais da receita.estabelecimentos ou carteira_raw
                $razaoSocial = 'Empresa Desconhecida';
                $endereco = $loc['endereco_formatado'] ?? 'Endereço não informado';

                $estabelecimento = $db->table('receita.estabelecimentos')
                                      ->where('cnpj_basico || cnpj_ordem || cnpj_dv', $loc['cnpj'])
                                      ->get()
                                      ->getRowArray();
                
                if ($estabelecimento) {
                    $endereco = trim($estabelecimento['tipo_logradouro'] . ' ' . $estabelecimento['logradouro'] . ', ' . $estabelecimento['numero']);
                }

                if ($carteira) {
                    $razaoSocial = $carteira['razao_social'];
                } else {
                    $empresa = $db->table('receita.empresas')
                                  ->where('cnpj_basico', substr($loc['cnpj'], 0, 8))
                                  ->get()
                                  ->getRowArray();
                    if ($empresa) {
                        $razaoSocial = $empresa['razao_social'];
                    }
                }

                $proximas[] = [
                    'cnpj'         => $loc['cnpj'],
                    'razao_social' => $razaoSocial,
                    'endereco'     => $endereco,
                    'latitude'     => (float)$loc['latitude'],
                    'longitude'    => (float)$loc['longitude'],
                    'distancia'    => $dist,
                    'status'       => $status
                ];
            }
        }

        // Ordena por menor distância
        usort($proximas, function($a, $b) {
            return $a['distancia'] <=> $b['distancia'];
        });

        return $this->response->setJSON(['empresas' => $proximas]);
    }

    public function preVisitaSalvar()
    {
        $vendorUser = $this->getVendorUser();
        if (!$vendorUser) return $this->response->setJSON(['error' => 'Sem carteira'])->setStatusCode(403);

        $bairro = $this->request->getPost('bairro');
        $userLat = (float) $this->request->getPost('lat');
        $userLng = (float) $this->request->getPost('lng');

        if (empty($bairro)) {
            return $this->response->setJSON(['error' => 'Bairro não informado.'])->setStatusCode(422);
        }

        $db = db_connect();
        
        // Busca estabelecimentos com base no bairro informado
        $estabelecimentos = $db->table('receita.estabelecimentos')
                              ->where('LOWER(bairro) LIKE LOWER(?)', ["%{$bairro}%"])
                              ->limit(20) // Limite de amostragem por busca
                              ->get()
                              ->getResultArray();

        if (empty($estabelecimentos)) {
            return $this->response->setJSON(['success' => false, 'message' => 'Nenhum estabelecimento encontrado neste bairro.']);
        }

        $locationModel = new ClientLocationModel();

        // Adiciona coordenadas aproximadas em torno das coordenadas do usuário
        // simulando distribuição geográfica das empresas no bairro visitado
        foreach ($estabelecimentos as $idx => $est) {
            $cnpj = $est['cnpj_basico'] . $est['cnpj_ordem'] . $est['cnpj_dv'];
            
            // Pequena variação aleatória de coordenadas em torno do GPS atual do vendedor
            // para fazer os pontos aparecerem espalhados no mapa da região
            $offsetLat = (rand(-100, 100) / 10000.0);
            $offsetLng = (rand(-100, 100) / 10000.0);

            $lat = $userLat + $offsetLat;
            $lng = $userLng + $offsetLng;

            $endereco = trim(($est['tipo_logradouro'] ?? '') . ' ' . ($est['logradouro'] ?? '') . ', ' . ($est['numero'] ?? ''));

            $locationModel->upsert([
                'cnpj'               => $cnpj,
                'latitude'           => $lat,
                'longitude'          => $lng,
                'endereco_formatado' => $endereco ?: 'Endereço Indisponível',
                'registrado_por'     => $vendorUser['matricula']
            ]);
        }

        return $this->response->setJSON(['success' => true]);
    }

    /**
     * Consulta a API pública do BrasilAPI para checar a situação cadastral do CNPJ.
     * 
     * A BrasilAPI tem limite de 3 requisições por segundo por IP.
     * Se rate-limited (HTTP 429), aguarda 1s e tenta novamente uma vez.
     */
    public function verificarCnpj(string $cnpj)
    {
        $vendorUser = $this->getVendorUser();
        if (!$vendorUser) {
            return $this->response->setJSON(['error' => 'Não autorizado'])->setStatusCode(403);
        }

        // Limpa o CNPJ (apenas dígitos)
        $cleanCnpj = preg_replace('/[^0-9]/', '', $cnpj);
        if (strlen($cleanCnpj) !== 14) {
            return $this->response->setJSON(['error' => 'CNPJ deve possuir 14 dígitos.'])->setStatusCode(400);
        }

        // ── Verificação no cache local primeiro ──
        $db = db_connect();
        $local = $db->table('client_wallets')
                    ->select('rfb_situacao_cadastral, rfb_verificado_em')
                    ->where('cnpj', $cleanCnpj)
                    ->where('rfb_verificado_em IS NOT NULL')
                    ->get()
                    ->getRowArray();

        if ($local && !empty($local['rfb_verificado_em'])) {
            $diffHours = (time() - strtotime($local['rfb_verificado_em'])) / 3600;
            // Se foi verificado há menos de 24h, usa cache local
            if ($diffHours < 24) {
                $isAtivo = (strtoupper(trim($local['rfb_situacao_cadastral'])) === 'ATIVA');
                return $this->response->setJSON([
                    'success'            => true,
                    'cnpj'               => $cleanCnpj,
                    'situacao_cadastral' => $local['rfb_situacao_cadastral'],
                    'ativo'              => $isAtivo,
                    'verificado_em'      => date('d/m/Y H:i', strtotime($local['rfb_verificado_em'])),
                    'cache'              => true,
                ]);
            }
        }

        // ── Consulta à API externa com retry ──
        $client = \Config\Services::curlrequest();
        $maxAttempts = 2;

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                $response = $client->get("https://brasilapi.com.br/api/cnpj/v1/{$cleanCnpj}", [
                    'headers' => [
                        'User-Agent' => 'SPIV-App/1.0',
                    ],
                    'timeout' => 8,
                    'http_errors' => false,
                ]);

                $statusCode = $response->getStatusCode();

                // Rate limit — aguarda e tenta novamente
                if ($statusCode === 429) {
                    if ($attempt < $maxAttempts) {
                        sleep(1);
                        continue;
                    }
                    return $this->response->setJSON([
                        'success' => false,
                        'error' => 'A API pública de CNPJ está temporariamente indisponível devido a limite de requisições. Tente novamente em alguns instantes.',
                    ]);
                }

                // Erro 404 = CNPJ não encontrado na base da Receita
                if ($statusCode === 404) {
                    return $this->response->setJSON([
                        'success' => false,
                        'error' => 'CNPJ não encontrado na base da Receita Federal.',
                    ]);
                }

                // Outros erros
                if ($statusCode !== 200) {
                    return $this->response->setJSON([
                        'success' => false,
                        'error' => 'API pública retornou erro inesperado (HTTP ' . $statusCode . '). Tente novamente mais tarde.',
                    ]);
                }

                // Sucesso
                $data = json_decode($response->getBody(), true);
                $situacao = $data['descricao_situacao_cadastral'] ?? 'DESCONHECIDA';
                $isAtivo = (strtoupper(trim($situacao)) === 'ATIVA');

                // Persistir no banco
                $now = date('Y-m-d H:i:s');
                $exists = $db->table('client_wallets')->where('cnpj', $cleanCnpj)->get()->getRow();
                if ($exists) {
                    $db->table('client_wallets')
                       ->where('cnpj', $cleanCnpj)
                       ->update([
                           'rfb_situacao_cadastral' => $situacao,
                           'rfb_verificado_em'      => $now,
                       ]);
                } else {
                    $db->table('client_wallets')->insert([
                        'cnpj'                   => $cleanCnpj,
                        'rfb_situacao_cadastral' => $situacao,
                        'rfb_verificado_em'      => $now,
                        'vendor_id'              => $vendorUser['id'],
                        'status_operacional'     => 'novo',
                        'created_at'             => $now,
                        'updated_at'             => $now,
                    ]);
                }

                return $this->response->setJSON([
                    'success'            => true,
                    'cnpj'               => $cleanCnpj,
                    'situacao_cadastral' => $situacao,
                    'ativo'              => $isAtivo,
                    'verificado_em'      => date('d/m/Y H:i', strtotime($now)),
                    'razao_social'       => $data['razao_social'] ?? '',
                    'nome_fantasia'      => $data['nome_fantasia'] ?? '',
                ]);

            } catch (\Exception $e) {
                // Se não for a última tentativa, tenta novamente
                if ($attempt < $maxAttempts) {
                    sleep(1);
                    continue;
                }
                return $this->response->setJSON([
                    'success' => false,
                    'error'   => 'Erro de conexão com a API pública: ' . $e->getMessage(),
                ]);
            }
        }

        // Fallback (não deve chegar aqui)
        return $this->response->setJSON([
            'success' => false,
            'error' => 'Não foi possível consultar a situação cadastral no momento.',
        ]);
    }

    /**
     * Busca as coordenadas (lat/lng) do endereço do cliente usando Nominatim OpenStreetMap.
     */
    public function geolocalizarCnpj(string $cnpj)
    {
        $vendorUser = $this->getVendorUser();
        if (!$vendorUser) {
            return $this->response->setJSON(['error' => 'Não autorizado'])->setStatusCode(403);
        }

        $cleanCnpj = preg_replace('/[^0-9]/', '', $cnpj);
        if (strlen($cleanCnpj) !== 14) {
            return $this->response->setJSON(['error' => 'CNPJ inválido'])->setStatusCode(400);
        }

        $db = db_connect();

        // 1. Carrega os dados de endereço da receita.estabelecimentos
        $est = $db->query("
            SELECT e.*, m.descricao AS municipio_nome
            FROM receita.estabelecimentos e
            LEFT JOIN receita.municipios m ON e.municipio = m.codigo
            WHERE (e.cnpj_basico || e.cnpj_ordem || e.cnpj_dv) = ?
            LIMIT 1
        ", [$cleanCnpj])->getRowArray();

        if (!$est) {
            return $this->response->setJSON(['success' => false, 'error' => 'Dados de endereço não encontrados no banco da Receita.']);
        }

        // Construir string de busca para a API de Geocoding
        $logradouroCompleto = trim(($est['tipo_logradouro'] ?? '') . ' ' . ($est['logradouro'] ?? ''));
        $numero = trim($est['numero'] ?? '');
        $bairro = trim($est['bairro'] ?? '');
        $cidade = trim($est['municipio_nome'] ?? '');
        $uf = trim($est['uf'] ?? '');
        $cep = trim($est['cep'] ?? '');

        if (empty($logradouroCompleto) || empty($cidade)) {
            return $this->response->setJSON(['success' => false, 'error' => 'Endereço incompleto no cadastro para geolocalização.']);
        }

        // String para Nominatim
        $addressQuery = "{$logradouroCompleto}, {$numero} - {$bairro}, {$cidade} - {$uf}, {$cep}, Brasil";
        $fallbackQuery = "{$logradouroCompleto}, {$cidade} - {$uf}, Brasil";

        $client = \Config\Services::curlrequest();
        $lat = null;
        $lon = null;

        // Tenta buscar pelo endereço completo
        try {
            $response = $client->get('https://nominatim.openstreetmap.org/search', [
                'headers' => [
                    'User-Agent' => 'SPIV-App/1.0 (contact@spiv.dev)',
                    'Accept-Language' => 'pt-BR,pt;q=0.9',
                ],
                'query' => [
                    'q' => $addressQuery,
                    'format' => 'json',
                    'limit' => 1,
                    'countrycodes' => 'br'
                ],
                'timeout' => 5,
                'http_errors' => false
            ]);

            if ($response->getStatusCode() === 200) {
                $results = json_decode($response->getBody(), true);
                if (!empty($results[0])) {
                    $lat = $results[0]['lat'];
                    $lon = $results[0]['lon'];
                }
            }

            // Se falhar, tenta o fallback (sem o número e bairro)
            if (($lat === null || $lon === null)) {
                $responseFallback = $client->get('https://nominatim.openstreetmap.org/search', [
                    'headers' => [
                        'User-Agent' => 'SPIV-App/1.0 (contact@spiv.dev)',
                        'Accept-Language' => 'pt-BR,pt;q=0.9',
                    ],
                    'query' => [
                        'q' => $fallbackQuery,
                        'format' => 'json',
                        'limit' => 1,
                        'countrycodes' => 'br'
                    ],
                    'timeout' => 5,
                    'http_errors' => false
                ]);

                if ($responseFallback->getStatusCode() === 200) {
                    $resultsFallback = json_decode($responseFallback->getBody(), true);
                    if (!empty($resultsFallback[0])) {
                        $lat = $resultsFallback[0]['lat'];
                        $lon = $resultsFallback[0]['lon'];
                    }
                }
            }

            if ($lat === null || $lon === null) {
                return $this->response->setJSON([
                    'success' => false,
                    'error' => 'Endereço não localizado no mapa pela API pública.'
                ]);
            }

            // 2. Persistir na tabela client_locations
            $enderecoFormatado = trim("{$logradouroCompleto}, {$numero}, {$bairro}, {$cidade} - {$uf}");
            $exists = $db->table('client_locations')->where('cnpj', $cleanCnpj)->get()->getRow();

            $locationData = [
                'cnpj'               => $cleanCnpj,
                'latitude'           => (float) $lat,
                'longitude'          => (float) $lon,
                'endereco_formatado' => $enderecoFormatado,
                'registrado_por'     => $vendorUser['matricula'],
                'updated_at'         => date('Y-m-d H:i:s')
            ];

            if ($exists) {
                $db->table('client_locations')->where('cnpj', $cleanCnpj)->update($locationData);
            } else {
                $locationData['created_at'] = date('Y-m-d H:i:s');
                $db->table('client_locations')->insert($locationData);
            }

            return $this->response->setJSON([
                'success'   => true,
                'latitude'  => $lat,
                'longitude' => $lon,
                'endereco'  => $enderecoFormatado
            ]);

        } catch (\Exception $e) {
            return $this->response->setJSON([
                'success' => false,
                'error'   => 'Erro de conexão com geocoding: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * OSINT: Busca redes sociais da empresa usando DuckDuckGo HTML Search e persiste como sugestão.
     */
    public function buscarRedesSociais(string $cnpj)
    {
        $vendorUser = $this->getVendorUser();
        if (!$vendorUser) {
            return $this->response->setJSON(['error' => 'Não autorizado'])->setStatusCode(403);
        }

        $cleanCnpj = preg_replace('/[^0-9]/', '', $cnpj);
        if (strlen($cleanCnpj) !== 14) {
            return $this->response->setJSON(['error' => 'CNPJ inválido'])->setStatusCode(400);
        }

        $db = db_connect();

        // Carrega razão social e cidade do cliente para busca direcionada
        $cliente = $db->query("
            SELECT c.razao_social, e.nome_fantasia, m.descricao AS municipio_nome
            FROM carteira_raw c
            LEFT JOIN receita.estabelecimentos e ON (e.cnpj_basico || e.cnpj_ordem || e.cnpj_dv) = c.cnpj
            LEFT JOIN receita.municipios m ON e.municipio = m.codigo
            WHERE c.cnpj = ?
            LIMIT 1
        ", [$cleanCnpj])->getRowArray();

        if (!$cliente) {
            return $this->response->setJSON(['success' => false, 'error' => 'Cliente não cadastrado.']);
        }

        $nomeBusca = !empty($cliente['nome_fantasia']) ? $cliente['nome_fantasia'] : $cliente['razao_social'];
        $cidade = $cliente['municipio_nome'] ?? '';

        // Monta a query para pesquisar
        $searchQuery = trim("{$nomeBusca} {$cidade} (site:instagram.com OR site:linkedin.com/company OR site:facebook.com)");

        $client = \Config\Services::curlrequest();
        $sugestoes = [];

        try {
            $response = $client->get('https://html.duckduckgo.com/html/', [
                'headers' => [
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                    'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8',
                    'Accept-Language' => 'pt-BR,pt;q=0.9,en;q=0.8',
                ],
                'query' => [
                    'q' => $searchQuery
                ],
                'timeout' => 6,
                'http_errors' => false
            ]);

            if ($response->getStatusCode() === 200) {
                $html = $response->getBody();
                
                // Encontrar links de instagram, linkedin e facebook
                preg_match_all('/href="([^"]*?(?:instagram\.com|linkedin\.com|facebook\.com)[^"]*?)"/i', $html, $matches);

                if (!empty($matches[1])) {
                    $uniqueUrls = [];
                    foreach ($matches[1] as $url) {
                        // Tratar redirecionamento do DuckDuckGo se houver uddg=
                        if (strpos($url, 'uddg=') !== false) {
                            parse_str(parse_url($url, PHP_URL_QUERY), $queryParts);
                            if (!empty($queryParts['uddg'])) {
                                $url = $queryParts['uddg'];
                            }
                        }

                        $url = filter_var($url, FILTER_VALIDATE_URL);
                        if ($url && !in_array($url, $uniqueUrls, true)) {
                            $uniqueUrls[] = $url;

                            // Identificar a rede social
                            $network = 'website';
                            if (strpos($url, 'instagram.com') !== false) {
                                $network = 'instagram';
                            } elseif (strpos($url, 'linkedin.com') !== false) {
                                $network = 'linkedin';
                            } elseif (strpos($url, 'facebook.com') !== false) {
                                $network = 'facebook';
                            }

                            // Ignorar links genericos de login ou compartilhamento
                            if (preg_match('/(login|share|status|hashtag|directory|post|jobs|pulse)/i', $url)) {
                                continue;
                            }

                            $sugestoes[] = [
                                'cnpj'       => $cleanCnpj,
                                'network'    => $network,
                                'url'        => $url,
                                'status'     => 'sugestao',
                                'created_at' => date('Y-m-d H:i:s'),
                                'updated_at' => date('Y-m-d H:i:s')
                            ];
                        }
                    }
                }
            }

            // Gravar sugestões no banco de dados com ON CONFLICT (ignorar duplicatas)
            if (!empty($sugestoes)) {
                foreach ($sugestoes as $sug) {
                    try {
                        $db->table('client_social_media')->insert($sug);
                    } catch (\Exception $ex) {
                        // Ignora erro de chave única duplicada
                    }
                }
            }

            // Recarrega as redes ativas (sugestão ou validadas)
            $redes = $db->table('client_social_media')
                        ->where('cnpj', $cleanCnpj)
                        ->where('status !=', 'rejeitado')
                        ->orderBy('status', 'DESC')
                        ->get()
                        ->getResultArray();

            return $this->response->setJSON([
                'success' => true,
                'redes'   => $redes
            ]);

        } catch (\Exception $e) {
            return $this->response->setJSON([
                'success' => false,
                'error'   => 'Erro de OSINT: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Valida uma sugestão de rede social.
     */
    public function validarRedeSocial(int $id)
    {
        $vendorUser = $this->getVendorUser();
        if (!$vendorUser) {
            return $this->response->setJSON(['error' => 'Não autorizado'])->setStatusCode(403);
        }

        $db = db_connect();
        
        // Verifica se a rede social pertence a um cliente da carteira deste vendedor
        $row = $db->table('client_social_media')->where('id', $id)->get()->getRowArray();
        if (!$row) {
            return $this->response->setJSON(['success' => false, 'error' => 'Registro não encontrado.']);
        }

        $belongs = $db->table('carteira_raw')
                      ->where('cnpj', $row['cnpj'])
                      ->where('matricula_mcmcu', $vendorUser['matricula'])
                      ->countAllResults();

        if (!$belongs) {
            return $this->response->setJSON(['error' => 'Não autorizado'])->setStatusCode(403);
        }

        $db->table('client_social_media')
           ->where('id', $id)
           ->update([
               'status'     => 'validado',
               'updated_at' => date('Y-m-d H:i:s')
           ]);

        return $this->response->setJSON(['success' => true, 'message' => 'Rede social validada.']);
    }

    /**
     * Rejeita/deleta uma sugestão de rede social.
     */
    public function rejeitarRedeSocial(int $id)
    {
        $vendorUser = $this->getVendorUser();
        if (!$vendorUser) {
            return $this->response->setJSON(['error' => 'Não autorizado'])->setStatusCode(403);
        }

        $db = db_connect();
        
        $row = $db->table('client_social_media')->where('id', $id)->get()->getRowArray();
        if (!$row) {
            return $this->response->setJSON(['success' => false, 'error' => 'Registro não encontrado.']);
        }

        $belongs = $db->table('carteira_raw')
                      ->where('cnpj', $row['cnpj'])
                      ->where('matricula_mcmcu', $vendorUser['matricula'])
                      ->countAllResults();

        if (!$belongs) {
            return $this->response->setJSON(['error' => 'Não autorizado'])->setStatusCode(403);
        }

        // Marcamos como rejeitado para não aparecer mais
        $db->table('client_social_media')
           ->where('id', $id)
           ->update([
               'status'     => 'rejeitado',
               'updated_at' => date('Y-m-d H:i:s')
           ]);

        return $this->response->setJSON(['success' => true, 'message' => 'Sugestão removida.']);
    }
}
