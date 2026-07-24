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
        $totalNotas   = (int) ($db->query("SELECT COUNT(*) AS c FROM vendor_notes WHERE matricula_vendedor = ?", [$mat])->getRow()->c ?? 0);
        $isCoordenador = $this->vendorModel->isCoordinator($mat);

        return view('vendedor/dashboard', compact('vendorUser', 'totalClientes', 'categorias', 'ciclos', 'segmentos', 'ultimasNotas', 'totalNotas', 'isCoordenador'));
    }

    /**
     * Tela dedicada "Minhas Notas": Central de Histórico e Pesquisa de Notas do Vendedor.
     */
    public function minhasNotas()
    {
        $vendorUser = $this->getVendorUser();
        if (!$vendorUser) return redirect()->to('/sem-carteira');

        $tipo    = $this->request->getGet('tipo') ?? '';
        $publica = $this->request->getGet('publica') ?? '';
        $busca   = trim($this->request->getGet('busca') ?? '');

        $noteModel = new VendorNoteModel();
        $mat = $vendorUser['matricula'];

        $notas = $noteModel->getAllByVendor($mat, [
            'tipo'    => $tipo,
            'publica' => $publica,
            'busca'   => $busca,
        ]);

        $contadores = $noteModel->countByType($mat);
        $totaisPorTipo = array_column($contadores, 'total', 'tipo');
        $totalGeral = array_sum($totaisPorTipo);

        return view('vendedor/minhas_notas', compact('vendorUser', 'notas', 'tipo', 'publica', 'busca', 'totaisPorTipo', 'totalGeral'));
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

    public function clientesMapaView()
    {
        $vendorUser = $this->getVendorUser();
        if (!$vendorUser) return redirect()->to('/sem-carteira');
        return view('vendedor/clientes_mapa', compact('vendorUser'));
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

        $cnpjs = array_column($clientes, 'cnpj');
        $bulkCnaes = $this->getBulkCnaesDetalhados($cnpjs);

        foreach ($clientes as &$c) {
            $cleanC = preg_replace('/[^0-9]/', '', $c['cnpj']);
            $c['cnaes_detalhados'] = $bulkCnaes[$cleanC] ?? [];
        }

        return $this->response->setJSON(['total' => count($clientes), 'clientes' => $clientes]);
    }

    /**
     * Retorna os clientes da carteira do vendedor que possuem coordenadas mapeadas.
     * Usado pela tela de mapa de carteira.
     */
    public function clientesMapaApi()
    {
        $vendorUser = $this->getVendorUser();
        if (!$vendorUser) return $this->response->setJSON(['error' => 'Sem carteira'])->setStatusCode(403);

        $db = db_connect();

        $rows = $db->query("
            SELECT
                c.cnpj,
                c.razao_social,
                c.categoria,
                c.segmento_mercado,
                c.ciclo_de_vida,
                c.cnae,
                cl.latitude,
                cl.longitude,
                COALESCE(cw.status_operacional, 'ativo') AS status_operacional,
                COALESCE(ce.logistics_score, 0) AS score
            FROM carteira_raw c
            JOIN client_locations cl ON cl.cnpj = REGEXP_REPLACE(c.cnpj, '[^0-9]', '', 'g')
            LEFT JOIN client_wallets cw ON cw.cnpj = REGEXP_REPLACE(c.cnpj, '[^0-9]', '', 'g')
            LEFT JOIN client_enrichment ce ON ce.cnpj = REGEXP_REPLACE(c.cnpj, '[^0-9]', '', 'g')
            WHERE c.matricula_mcmcu = ?
              AND cl.latitude IS NOT NULL
              AND cl.longitude IS NOT NULL
            ORDER BY c.razao_social ASC
        ", [$vendorUser['matricula']])->getResultArray();

        return $this->response->setJSON([
            'success'  => true,
            'total'    => count($rows),
            'clientes' => $rows,
        ]);
    }

    /**
     * Retorna todos os CNPJs com coordenadas em client_locations
     * que NÃO pertencem à carteira DO VENDEDOR LOGADO.
     * Inclui o campo 'ocupado' = true quando pertence a OUTRO vendedor (triângulo vermelho),
     * false = livre/fora de qualquer carteira (losango verde).
     */
    public function livresMapaApi()
    {
        $vendorUser = $this->getVendorUser();
        if (!$vendorUser) return $this->response->setJSON(['error' => 'Sem acesso'])->setStatusCode(403);

        $matricula = $vendorUser['matricula'];
        $db = db_connect();

        $rows = $db->query("
            SELECT
                cl.cnpj,
                cl.latitude,
                cl.longitude,
                COALESCE(cr_outra.razao_social, emp.razao_social, cl.cnpj) AS razao_social,
                COALESCE(est.cnae_fiscal_principal, '') AS cnae,
                COALESCE(ce.logistics_score, 0) AS score,
                -- true = pertence a outro vendedor; false = fora de qualquer carteira (livre)
                (cr_outra.cnpj IS NOT NULL) AS ocupado,
                cr_outra.matricula_mcmcu AS outro_vendedor_matricula,
                COALESCE(vu.nome, cr_outra.forca_vendas_nome, cr_outra.matricula_mcmcu) AS outro_vendedor_nome,
                cr_outra.gerencia AS outro_vendedor_gerencia
            FROM client_locations cl
            LEFT JOIN carteira_raw cr_outra
                   ON REGEXP_REPLACE(cr_outra.cnpj, '[^0-9]', '', 'g') = cl.cnpj
                  AND cr_outra.matricula_mcmcu != ?
            LEFT JOIN vendor_users vu
                   ON vu.matricula = cr_outra.matricula_mcmcu
            LEFT JOIN receita.empresas emp
                   ON emp.cnpj_basico = SUBSTRING(cl.cnpj, 1, 8)
            LEFT JOIN receita.estabelecimentos est
                   ON (est.cnpj_basico || est.cnpj_ordem || est.cnpj_dv) = cl.cnpj
            LEFT JOIN client_enrichment ce ON ce.cnpj = cl.cnpj
            WHERE cl.latitude IS NOT NULL
              AND cl.longitude IS NOT NULL
              -- Exclui os que já são da carteira DO próprio vendedor logado
              AND cl.cnpj NOT IN (
                  SELECT DISTINCT REGEXP_REPLACE(cnpj, '[^0-9]', '', 'g') FROM carteira_raw
                  WHERE matricula_mcmcu = ?
                    AND cnpj IS NOT NULL
              )
            ORDER BY ce.logistics_score DESC NULLS LAST
            LIMIT 5000
        ", [$matricula, $matricula])->getResultArray();

        return $this->response->setJSON([
            'success' => true,
            'total'   => count($rows),
            'livres'  => $rows,
        ]);
    }

    // ─── Detalhe do Cliente ──────────────────────────────────────

    public function clienteDetalhe(string $cnpj)
    {
        $vendorUser = $this->getVendorUser();
        if (!$vendorUser) return redirect()->to('/sem-carteira');

        $db = db_connect();

        // Verifica se o CNPJ já é da carteira do vendedor logado
        $existsRaw = $db->table('carteira_raw')
                        ->where('cnpj', $cnpj)
                        ->where('matricula_mcmcu', $vendorUser['matricula'])
                        ->get()
                        ->getRow();

        // NÃO auto-adiciona mais. Se não pertence ao vendedor, abre em modo prospecto.
        $modoProspecto = !$existsRaw;

        if ($modoProspecto) {
            // ── Modo Prospecto: dados públicos da Receita Federal ──────────
            // O vendedor pode visualizar QUALQUER CNPJ — a tela é pública para leitura.
            // Nenhum dado de carteira exclusivo é exibido.
            $receitaRow = $db->query("
                SELECT
                    c.cnpj AS cnpj,
                    emp.razao_social,
                    'Prospecto' AS categoria,
                    NULL AS segmento_cliente,
                    NULL AS segmento_mercado,
                    NULL AS ciclo_de_vida,
                    NULL AS canais_vendas,
                    NULL AS forca_vendas_nome,
                    NULL AS matricula_mcmcu,
                    NULL AS conta_numero,
                    NULL AS conta_nome,
                    NULL AS gerencia,
                    NULL AS nat_juridica,
                    emp.capital_social,
                    e.tipo_logradouro, e.logradouro, e.numero, e.complemento,
                    e.bairro, e.cep, e.uf,
                    m.descricao AS municipio_nome,
                    e.ddd_1, e.telefone_1, e.ddd_2, e.telefone_2,
                    e.email, e.cnae_fiscal_principal AS cnae,
                    CASE e.situacao_cadastral
                        WHEN '01' THEN 'Nula' WHEN '02' THEN 'Ativa'
                        WHEN '03' THEN 'Suspensa' WHEN '04' THEN 'Inapta' WHEN '08' THEN 'Baixada'
                        ELSE 'Situação ' || COALESCE(e.situacao_cadastral, '?')
                    END AS situacao_desc,
                    cw.rfb_situacao_cadastral, cw.rfb_verificado_em,
                    loc.latitude AS loc_lat, loc.longitude AS loc_lng
                FROM receita.estabelecimentos e
                JOIN (SELECT ? AS cnpj) c ON true
                LEFT JOIN receita.empresas emp ON emp.cnpj_basico = e.cnpj_basico
                LEFT JOIN receita.municipios m ON m.codigo = e.municipio
                LEFT JOIN client_wallets cw ON cw.cnpj = c.cnpj
                LEFT JOIN client_locations loc ON loc.cnpj = c.cnpj
                WHERE (e.cnpj_basico || e.cnpj_ordem || e.cnpj_dv) = ?
                LIMIT 1
            ", [$cnpj, $cnpj])->getRowArray();

            // Se não encontrou na Receita, retorna dados mínimos com o CNPJ
            $cliente = $receitaRow ?: [
                'cnpj'        => $cnpj,
                'razao_social'=> 'CNPJ não localizado na base da Receita',
                'categoria'   => 'Prospecto',
            ];
        } else {
            // ── Modo Carteira: dados completos da carteira do vendedor ──────
            $cliente = $db->query("
                SELECT c.*,
                       emp.capital_social,
                       cw.rfb_situacao_cadastral, cw.rfb_verificado_em,
                       loc.latitude AS loc_lat, loc.longitude AS loc_lng,
                       e.tipo_logradouro, e.logradouro, e.numero, e.complemento, e.bairro, e.cep, e.uf,
                       m.descricao AS municipio_nome,
                       e.ddd_1, e.telefone_1, e.ddd_2, e.telefone_2,
                       e.email, e.cnae_fiscal_principal AS cnae
                FROM carteira_raw c
                LEFT JOIN receita.empresas emp ON emp.cnpj_basico = SUBSTRING(c.cnpj,1,8)
                LEFT JOIN client_wallets cw ON cw.cnpj = c.cnpj
                LEFT JOIN client_locations loc ON loc.cnpj = c.cnpj
                LEFT JOIN receita.estabelecimentos e ON (e.cnpj_basico || e.cnpj_ordem || e.cnpj_dv) = c.cnpj
                LEFT JOIN receita.municipios m ON e.municipio = m.codigo
                WHERE c.cnpj = ? AND c.matricula_mcmcu = ?
                LIMIT 1
            ", [$cnpj, $vendorUser['matricula']])->getRowArray();

            if (!$cliente) {
                return redirect()->to('/vendedor')->with('error', 'Cliente não encontrado.');
            }
        }

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

        // Verifica se já existe PR-CAP pendente/mais_info para este CNPJ + vendedor
        $pedidoExistente = null;
        if ($modoProspecto) {
            $pedidoExistente = $db->table('captacao_requests')
                ->where('cnpj', $cnpj)
                ->where('matricula', $vendorUser['matricula'])
                ->whereIn('status', ['pendente', 'mais_info'])
                ->get()->getRow();
        }

        // Carrega CNAEs detalhados (Principal + Secundários com descrições)
        $cleanCnpj = preg_replace('/[^0-9]/', '', $cnpj);
        $cnaesDetalhados = $this->getCnaesDetalhados($cleanCnpj);

        return view('vendedor/cliente_detalhe', compact('vendorUser', 'cliente', 'notas', 'estrategias', 'redesSociais', 'modoProspecto', 'pedidoExistente', 'cnaesDetalhados'));

    }

    // ─── Captação de Clientes (PR-CAP) ───────────────────────────

    /**
     * Exibe o formulário de Pedido de Captação (PR-CAP) para um CNPJ.
     * Pré-carrega os logs do sistema como evidências.
     */
    public function captacaoSolicitar(string $cnpj)
    {
        $vendorUser = $this->getVendorUser();
        if (!$vendorUser) return redirect()->to('/sem-carteira');

        $cleanCnpj = preg_replace('/[^0-9]/', '', $cnpj);
        $db = db_connect();
        $mat = $vendorUser['matricula'];

        // Impede se o CNPJ já está na carteira do próprio vendedor
        $naCarteira = $db->table('carteira_raw')
            ->where('cnpj', $cleanCnpj)
            ->where('matricula_mcmcu', $mat)
            ->countAllResults();
        if ($naCarteira > 0) {
            return redirect()->to(site_url("vendedor/cliente/{$cleanCnpj}"))->with('info', 'Este cliente já está na sua carteira.');
        }

        // Dados da Receita
        $receita = $db->query("
            SELECT e.*, emp.razao_social, m.descricao AS municipio_nome,
                   CASE e.situacao_cadastral
                       WHEN '01' THEN 'Nula' WHEN '02' THEN 'Ativa'
                       WHEN '03' THEN 'Suspensa' WHEN '04' THEN 'Inapta' WHEN '08' THEN 'Baixada'
                       ELSE 'Situacao ' || COALESCE(e.situacao_cadastral, '?')
                   END AS situacao_desc
            FROM receita.estabelecimentos e
            LEFT JOIN receita.empresas emp ON emp.cnpj_basico = e.cnpj_basico
            LEFT JOIN receita.municipios m ON m.codigo = e.municipio
            WHERE (e.cnpj_basico || e.cnpj_ordem || e.cnpj_dv) = ?
            LIMIT 1
        ", [$cleanCnpj])->getRowArray();

        // Verificar se o CNPJ pertence a outro vendedor
        $outraCarteira = $db->query("
            SELECT matricula_mcmcu, forca_vendas_nome FROM carteira_raw
            WHERE cnpj = ? AND matricula_mcmcu != ?
            LIMIT 1
        ", [$cleanCnpj, $mat])->getRowArray();

        // Score preditivo
        $enrichment = $db->table('client_enrichment')
            ->where('cnpj', $cleanCnpj)->get()->getRowArray();

        // Logs do sistema
        $locLog   = $db->table('client_locations')->where('cnpj', $cleanCnpj)->get()->getRowArray();
        $walletLog= $db->table('client_wallets')->where('cnpj', $cleanCnpj)->get()->getRowArray();
        $socialLog= $db->query("SELECT MAX(updated_at) AS dt FROM client_social_media WHERE cnpj = ?", [$cleanCnpj])->getRowArray();
        $notasLog = $db->query("SELECT COUNT(*) AS total, MAX(created_at) AS dt FROM vendor_notes WHERE cnpj = ? AND matricula_vendedor = ?", [$cleanCnpj, $mat])->getRowArray();

        // PR-CAP existente (pendente ou mais_info) — para modo de complementação
        $pedidoExistente = $db->table('captacao_requests')
            ->where('cnpj', $cleanCnpj)
            ->where('matricula', $mat)
            ->orderBy('created_at', 'DESC')
            ->get()->getRowArray();

        return view('vendedor/captacao_form', compact(
            'vendorUser', 'cleanCnpj', 'receita', 'outraCarteira',
            'enrichment', 'locLog', 'walletLog', 'socialLog', 'notasLog',
            'pedidoExistente'
        ));
    }

    /**
     * Grava o Pedido de Captação (PR-CAP) enviado pelo vendedor.
     */
    public function captacaoSalvar()
    {
        $vendorUser = $this->getVendorUser();
        if (!$vendorUser) return $this->response->setJSON(['error' => 'Não autorizado'])->setStatusCode(403);

        $cnpj          = preg_replace('/[^0-9]/', '', $this->request->getPost('cnpj'));
        $justificativa = trim($this->request->getPost('justificativa') ?? '');
        $tempoContato  = trim($this->request->getPost('tempo_contato') ?? '');
        $canais        = $this->request->getPost('canais_contato') ?? [];
        $referenciaDoc = trim($this->request->getPost('referencia_doc') ?? '');
        $mat           = $vendorUser['matricula'];

        if (strlen($cnpj) !== 14 || empty($justificativa)) {
            return redirect()->back()->with('error', 'Justificativa é obrigatória.');
        }

        $db = db_connect();

        // Bloqueia se já está na própria carteira
        $naCarteira = $db->table('carteira_raw')
            ->where('cnpj', $cnpj)->where('matricula_mcmcu', $mat)->countAllResults();
        if ($naCarteira > 0) {
            return redirect()->to(site_url("vendedor/cliente/{$cnpj}"))->with('info', 'Este cliente já está na sua carteira.');
        }

        // Verifica se CNPJ está em outra carteira (para registrar o contexto)
        $outra = $db->query("SELECT matricula_mcmcu FROM carteira_raw WHERE cnpj = ? AND matricula_mcmcu != ? LIMIT 1", [$cnpj, $mat])->getRowArray();

        // Se já existe PR-CAP pendente/mais_info do mesmo vendedor para o mesmo CNPJ → atualiza (complementação)
        $existente = $db->table('captacao_requests')
            ->where('cnpj', $cnpj)->where('matricula', $mat)
            ->whereIn('status', ['pendente', 'mais_info'])->get()->getRow();

        $data = [
            'cnpj'                   => $cnpj,
            'matricula'              => $mat,
            'justificativa'          => $justificativa,
            'tempo_contato'          => $tempoContato ?: null,
            'canais_contato'         => !empty($canais) ? json_encode($canais) : null,
            'referencia_doc'         => $referenciaDoc ?: null,
            'status'                 => 'pendente',
            'admin_obs'              => null,
            'cnpj_em_outra_carteira' => !empty($outra),
            'carteira_anterior'      => $outra['matricula_mcmcu'] ?? null,
            'updated_at'             => date('Y-m-d H:i:s'),
        ];

        if ($existente) {
            $db->table('captacao_requests')->where('id', $existente->id)->update($data);
            $captacaoId = $existente->id;
        } else {
            $data['created_at'] = date('Y-m-d H:i:s');
            // insertID() não funciona com PostgreSQL 12+ (pg_last_oid foi removido).
            // Usamos SELECT lastval() logo após o insert para pegar o ID correto.
            $db->table('captacao_requests')->insert($data);
            $captacaoId = (int) $db->query('SELECT lastval() AS id')->getRowArray()['id'];
        }

        // ── Upload de Anexos ────────────────────────────────────────────
        $arquivos = $this->request->getFiles();
        if (!empty($arquivos['anexos'])) {
            $uploadDir = WRITEPATH . 'uploads/captacoes/' . $captacaoId . '/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            foreach ($arquivos['anexos'] as $file) {
                if (!$file->isValid() || $file->hasMoved()) continue;

                $mime = $file->getMimeType();
                $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'application/pdf'];
                if (!in_array($mime, $allowed)) continue;

                if ($file->getSize() > 10 * 1024 * 1024) continue; // máx 10MB

                $newName = $file->getRandomName();
                $file->move($uploadDir, $newName);

                $db->table('captacao_attachments')->insert([
                    'captacao_id'   => $captacaoId,
                    'cnpj'          => $cnpj,
                    'matricula'     => $mat,
                    'filename'      => $newName,
                    'original_name' => $file->getClientName(),
                    'mime_type'     => $mime,
                    'file_size'     => $file->getSize(),
                    'created_at'    => date('Y-m-d H:i:s'),
                ]);
            }
        }

        return redirect()->to(site_url('vendedor/minhas-captacoes'))->with('success', 'Pedido enviado! Aguarde a análise administrativa.');
    }

    /**
     * Serve um anexo de forma protegida (apenas para o próprio vendedor ou admin).
     */
    public function captacaoAnexo(int $anexoId)
    {
        $vendorUser = $this->getVendorUser();
        if (!$vendorUser) return $this->response->setStatusCode(403)->setBody('Acesso negado.');

        $db = db_connect();
        $anexo = $db->table('captacao_attachments')
            ->where('id', $anexoId)
            ->where('matricula', $vendorUser['matricula'])
            ->get()->getRowArray();

        if (!$anexo) {
            return $this->response->setStatusCode(404)->setBody('Arquivo não encontrado.');
        }

        $path = WRITEPATH . 'uploads/captacoes/' . $anexo['captacao_id'] . '/' . $anexo['filename'];
        if (!file_exists($path)) {
            return $this->response->setStatusCode(404)->setBody('Arquivo removido do servidor.');
        }

        return $this->response
            ->setHeader('Content-Type', $anexo['mime_type'])
            ->setHeader('Content-Disposition', 'inline; filename="' . $anexo['original_name'] . '"')
            ->setBody(file_get_contents($path));
    }

    /**
     * Lista os PR-CAPs do vendedor logado.
     */
    public function minhasCaptacoes()
    {
        $vendorUser = $this->getVendorUser();
        if (!$vendorUser) return redirect()->to('/sem-carteira');

        $db = db_connect();
        $pedidos = $db->query("
            SELECT cr.*,
                   COALESCE(emp.razao_social, cr.cnpj) AS razao_social,
                   COALESCE(ce.logistics_score, 0) AS score
            FROM captacao_requests cr
            LEFT JOIN receita.empresas emp ON emp.cnpj_basico = SUBSTRING(cr.cnpj, 1, 8)
            LEFT JOIN client_enrichment ce ON ce.cnpj = cr.cnpj
            WHERE cr.matricula = ?
            ORDER BY cr.created_at DESC
        ", [$vendorUser['matricula']])->getResultArray();

        return view('vendedor/minhas_captacoes', compact('vendorUser', 'pedidos'));
    }

    // ─── Formulário de Nota ──────────────────────────────────────


    public function notaForm(string $cnpj)
    {
        $vendorUser = $this->getVendorUser();
        if (!$vendorUser) return redirect()->to('/sem-carteira');

        $cleanCnpj = preg_replace('/[^0-9]/', '', $cnpj);
        $db = db_connect();

        // 1. Tenta buscar na carteira do próprio vendedor logado
        $cliente = $db->query("
            SELECT cnpj, razao_social, categoria, segmento_mercado
            FROM carteira_raw
            WHERE REGEXP_REPLACE(cnpj, '[^0-9]', '', 'g') = ? AND matricula_mcmcu = ?
            LIMIT 1
        ", [$cleanCnpj, $vendorUser['matricula']])->getRowArray();

        // 2. Se não encontrou na sua carteira, busca na carteira_raw de outro vendedor ou na Receita Federal
        if (!$cliente) {
            $clienteRaw = $db->query("
                SELECT cnpj, razao_social, categoria, segmento_mercado
                FROM carteira_raw
                WHERE REGEXP_REPLACE(cnpj, '[^0-9]', '', 'g') = ?
                LIMIT 1
            ", [$cleanCnpj])->getRowArray();

            if ($clienteRaw) {
                $cliente = $clienteRaw;
            } else {
                // Busca dados públicos na Receita Federal
                $receita = $db->query("
                    SELECT (e.cnpj_basico || e.cnpj_ordem || e.cnpj_dv) AS cnpj,
                           emp.razao_social, 'Prospecto' AS categoria, '' AS segmento_mercado
                    FROM receita.estabelecimentos e
                    LEFT JOIN receita.empresas emp ON emp.cnpj_basico = e.cnpj_basico
                    WHERE (e.cnpj_basico || e.cnpj_ordem || e.cnpj_dv) = ?
                    LIMIT 1
                ", [$cleanCnpj])->getRowArray();

                $cliente = $receita ?: [
                    'cnpj'             => $cleanCnpj,
                    'razao_social'     => 'Cliente ' . $cleanCnpj,
                    'categoria'        => 'Prospecto',
                    'segmento_mercado' => '',
                ];
            }
        }

        return view('vendedor/nota_form', compact('vendorUser', 'cliente'));
    }

    /**
     * POST — Grava nota no banco.
     * Permitido para qualquer vendedor em qualquer cliente.
     */
    public function notaSalvar()
    {
        $vendorUser = $this->getVendorUser();
        if (!$vendorUser) return $this->response->setJSON(['error' => 'Sem carteira'])->setStatusCode(403);

        $cnpj       = preg_replace('/[^0-9]/', '', $this->request->getPost('cnpj') ?? '');
        $tipo       = $this->request->getPost('tipo');
        $conteudo   = trim($this->request->getPost('conteudo') ?? '');
        $sentimento = $this->request->getPost('sentimento');
        
        // Pública por padrão (true), a menos que explicitamente enviado '0', 'false' ou false
        $publicaVal = $this->request->getPost('publica');
        $publica    = ($publicaVal === '0' || $publicaVal === 'false' || $publicaVal === false) ? false : true;

        if (empty($cnpj) || empty($tipo) || empty($conteudo)) {
            return $this->response->setJSON(['error' => 'Campos obrigatórios não preenchidos.'])->setStatusCode(422);
        }

        $noteModel = new VendorNoteModel();
        $newId = $noteModel->insert([
            'matricula_vendedor' => $vendorUser['matricula'],
            'cnpj'               => $cnpj,
            'tipo'               => $tipo,
            'conteudo'           => $conteudo,
            'sentimento'         => $sentimento ?: null,
            'publica'            => $publica,
            'created_at'         => date('Y-m-d H:i:s'),
        ]);

        return $this->response->setJSON([
            'success' => true,
            'message' => 'Nota registrada com sucesso.',
            'nota_id' => $newId,
            'publica' => $publica,
        ]);
    }

    /**
     * POST /vendedor/nota/:id/visibilidade
     * Alterna publica/privada de uma nota. Apenas o autor pode alterar.
     */
    public function notaTogglePublica(int $id)
    {
        $vendorUser = $this->getVendorUser();
        if (!$vendorUser) {
            return $this->response->setJSON(['error' => 'Não autorizado'])->setStatusCode(403);
        }

        $noteModel = new VendorNoteModel();
        $ok = $noteModel->togglePublica($id, $vendorUser['matricula']);

        if (!$ok) {
            return $this->response->setJSON(['error' => 'Nota não encontrada ou sem permissão.'])->setStatusCode(404);
        }

        // Retorna o novo estado
        $nota = $noteModel->find($id);
        return $this->response->setJSON([
            'success' => true,
            'publica' => (bool) $nota['publica'],
        ]);
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

    /**
     * Retorna o Ranking de Potencial Logístico: leads livres (fora de qualquer carteira)
     * ordenados por logistics_score DESC. Usados na aba "Ranking" da prospecção.
     */
    public function rankingApi()
    {
        $vendorUser = $this->getVendorUser();
        if (!$vendorUser) {
            return $this->response->setJSON(['error' => 'Não autorizado'])->setStatusCode(403);
        }

        $db     = db_connect();
        $limit  = max(1, min(100, (int) ($this->request->getGet('limit') ?? 50)));
        $offset = max(0, (int) ($this->request->getGet('offset') ?? 0));

        // Só exibe CNPJs com score calculado, excluindo os já em carteira_raw
        $rows = $db->query("
            SELECT
                ce.cnpj,
                ce.logistics_score,
                ce.score_breakdown,
                ce.score_justification,
                COALESCE(e.nome_fantasia, '')     AS nome_fantasia,
                COALESCE(emp.razao_social, '')    AS razao_social,
                e.cnae_fiscal_principal,
                COALESCE(e.email, '')             AS email,
                COALESCE(
                    TRIM(e.tipo_logradouro || ' ' || e.logradouro || ', ' || e.numero
                        || CASE WHEN e.bairro <> '' THEN ' - ' || e.bairro ELSE '' END),
                    ''
                ) AS endereco_resumo,
                e.municipio AS municipio_codigo,
                e.uf,
                COALESCE(cl.latitude,  0) AS loc_lat,
                COALESCE(cl.longitude, 0) AS loc_lng
            FROM client_enrichment ce
            JOIN receita.estabelecimentos e
                ON (e.cnpj_basico || e.cnpj_ordem || e.cnpj_dv) = ce.cnpj
            JOIN receita.empresas emp ON emp.cnpj_basico = e.cnpj_basico
            LEFT JOIN client_locations cl ON cl.cnpj = ce.cnpj
            WHERE ce.logistics_score > 0
              AND ce.cnpj NOT IN (SELECT cnpj FROM carteira_raw WHERE cnpj IS NOT NULL)
            ORDER BY ce.logistics_score DESC
            LIMIT {$limit} OFFSET {$offset}
        ")->getResultArray();

        // Resolver nome dos municípios em batch
        $codigos = array_unique(array_filter(array_column($rows, 'municipio_codigo')));
        $munMap  = [];
        if (!empty($codigos)) {
            $placeholders = implode(',', array_fill(0, count($codigos), '?'));
            $munRows = $db->query("SELECT codigo, descricao FROM receita.municipios WHERE codigo IN ({$placeholders})", $codigos)->getResultArray();
            foreach ($munRows as $m) {
                $munMap[$m['codigo']] = $m['descricao'];
            }
        }

        foreach ($rows as &$row) {
            $row['municipio_nome']   = $munMap[$row['municipio_codigo']] ?? '';
            $row['score_breakdown']  = json_decode($row['score_breakdown'] ?? '{}', true);
        }

        // Contar total de leads livres com score
        $total = (int) ($db->query("
            SELECT COUNT(*) AS c FROM client_enrichment ce
            WHERE ce.logistics_score > 0
              AND ce.cnpj NOT IN (SELECT cnpj FROM carteira_raw WHERE cnpj IS NOT NULL)
        ")->getRow()->c ?? 0);

        return $this->response->setJSON([
            'success'  => true,
            'total'    => $total,
            'offset'   => $offset,
            'limit'    => $limit,
            'ranking'  => $rows,
        ]);
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

        $onlyCorpEmail = $this->request->getGet('only_corp_email') === '1';
        $emailFilterSql = "";
        if ($onlyCorpEmail) {
            // Rejeita emails nulos, vazios ou de provedores públicos/genéricos do Brasil e internacionais
            $emailFilterSql = " AND e.email IS NOT NULL AND e.email != '' AND e.email !~* '@(gmail\\.com|hotmail\\.com|yahoo\\.com|outlook\\.com|live\\.com|icloud\\.com|uol\\.com\\.br|bol\\.com\\.br|terra\\.com\\.br|ig\\.com\\.br|globomail\\.com|oi\\.com\\.br|pop\\.com\\.br|r7\\.com|zipmail\\.com\\.br|protonmail\\.com|zoho\\.com|aol\\.com|yandex\\.com|mail\\.com|msn\\.com|gmx\\.com)$' ";
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
                       cw.rfb_situacao_cadastral, cw.rfb_verificado_em,
                       COALESCE(ce.logistics_score, 0) AS logistics_score,
                       ce.score_breakdown,
                       (cr.cnpj IS NOT NULL) AS encarteirado,
                       cr.matricula_mcmcu AS vendedor_matricula,
                       COALESCE(vu.nome, cr.forca_vendas_nome, cr.matricula_mcmcu) AS vendedor_nome,
                       ra.status         AS ra_status,
                       ra.total          AS ra_total,
                       ra.pesquisado_em  AS ra_pesquisado_em
                FROM receita.estabelecimentos e
                LEFT JOIN receita.empresas emp ON e.cnpj_basico = emp.cnpj_basico
                LEFT JOIN receita.municipios m ON e.municipio = m.codigo
                LEFT JOIN client_locations loc ON loc.cnpj = (e.cnpj_basico || e.cnpj_ordem || e.cnpj_dv)
                LEFT JOIN client_wallets cw ON cw.cnpj = (e.cnpj_basico || e.cnpj_ordem || e.cnpj_dv)
                LEFT JOIN client_enrichment ce ON ce.cnpj = (e.cnpj_basico || e.cnpj_ordem || e.cnpj_dv)
                LEFT JOIN carteira_raw cr ON REGEXP_REPLACE(cr.cnpj, '[^0-9]', '', 'g') = (e.cnpj_basico || e.cnpj_ordem || e.cnpj_dv)
                LEFT JOIN vendor_users vu ON vu.matricula = cr.matricula_mcmcu
                LEFT JOIN client_ra_scans ra ON ra.cnpj = (e.cnpj_basico || e.cnpj_ordem || e.cnpj_dv)
                WHERE (e.cnpj_basico || e.cnpj_ordem || e.cnpj_dv) LIKE ?
                {$emailFilterSql}
                ORDER BY CASE WHEN cr.cnpj IS NOT NULL THEN 1 ELSE 0 END ASC,
                         COALESCE(ce.logistics_score, 0) DESC
                LIMIT 50
            ";
            $resultados = $db->query($query, ['%' . $cleanCnpj . '%'])->getResultArray();
        } else {
            $query = "
                SELECT (e.cnpj_basico || e.cnpj_ordem || e.cnpj_dv) AS cnpj,
                       emp.razao_social, e.nome_fantasia,
                       e.tipo_logradouro, e.logradouro, e.numero, e.complemento, e.bairro, e.cep, e.uf,
                       m.descricao AS municipio_nome,
                       loc.latitude AS loc_lat, loc.longitude AS loc_lng,
                       cw.rfb_situacao_cadastral, cw.rfb_verificado_em,
                       COALESCE(ce.logistics_score, 0) AS logistics_score,
                       ce.score_breakdown,
                       (cr.cnpj IS NOT NULL) AS encarteirado,
                       cr.matricula_mcmcu AS vendedor_matricula,
                       COALESCE(vu.nome, cr.forca_vendas_nome, cr.matricula_mcmcu) AS vendedor_nome,
                       ra.status         AS ra_status,
                       ra.total          AS ra_total,
                       ra.pesquisado_em  AS ra_pesquisado_em
                FROM receita.estabelecimentos e
                LEFT JOIN receita.empresas emp ON e.cnpj_basico = emp.cnpj_basico
                LEFT JOIN receita.municipios m ON e.municipio = m.codigo
                LEFT JOIN client_locations loc ON loc.cnpj = (e.cnpj_basico || e.cnpj_ordem || e.cnpj_dv)
                LEFT JOIN client_wallets cw ON cw.cnpj = (e.cnpj_basico || e.cnpj_ordem || e.cnpj_dv)
                LEFT JOIN client_enrichment ce ON ce.cnpj = (e.cnpj_basico || e.cnpj_ordem || e.cnpj_dv)
                LEFT JOIN carteira_raw cr ON REGEXP_REPLACE(cr.cnpj, '[^0-9]', '', 'g') = (e.cnpj_basico || e.cnpj_ordem || e.cnpj_dv)
                LEFT JOIN vendor_users vu ON vu.matricula = cr.matricula_mcmcu
                LEFT JOIN client_ra_scans ra ON ra.cnpj = (e.cnpj_basico || e.cnpj_ordem || e.cnpj_dv)
                WHERE (LOWER(emp.razao_social) LIKE ?
                   OR LOWER(e.nome_fantasia) LIKE ?
                   OR LOWER(e.logradouro) LIKE ?
                   OR LOWER(e.bairro) LIKE ?)
                   {$emailFilterSql}
                ORDER BY CASE WHEN cr.cnpj IS NOT NULL THEN 1 ELSE 0 END ASC,
                         COALESCE(ce.logistics_score, 0) DESC
                LIMIT 50
            ";
            $param = '%' . $searchTerm . '%';
            $resultados = $db->query($query, [$param, $param, $param, $param])->getResultArray();
        }

        $cnpjs = array_column($resultados, 'cnpj');
        $bulkCnaes = $this->getBulkCnaesDetalhados($cnpjs);

        foreach ($resultados as &$res) {
            $cleanC = preg_replace('/[^0-9]/', '', $res['cnpj']);
            $res['cnaes_detalhados'] = $bulkCnaes[$cleanC] ?? [];

            $isEncarteirado = !empty($res['encarteirado']) && ($res['encarteirado'] === true || $res['encarteirado'] === 't' || $res['encarteirado'] === '1' || $res['encarteirado'] === 1);
            $res['encarteirado'] = $isEncarteirado;

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

            // Normaliza campos do Reclame Aqui (NULL quando nunca pesquisado)
            $res['ra_status']       = $res['ra_status'] ?? null;
            $res['ra_total']        = isset($res['ra_total']) ? (int) $res['ra_total'] : null;
            $res['ra_pesquisado_em'] = $res['ra_pesquisado_em'] ?? null;
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
     * OSINT: Busca redes sociais da empresa em mecanismos de busca públicos.
     *
     * Estratégia em camadas:
     *   1. Tenta Bing HTML search (mais estável que DuckDuckGo para scraping simples)
     *   2. Se falhar, tenta DuckDuckGo HTML search (fallback)
     *   3. Se ambos falharem, retorna as redes já salvas no banco sem mensagem de erro
     *   4. Persiste novas sugestões no banco (ignorando duplicatas pela UNIQUE KEY)
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

        // Carrega razão social, e-mail e cidade do cliente para busca direcionada
        $cliente = $db->query("
            SELECT c.razao_social, e.nome_fantasia, e.email, m.descricao AS municipio_nome
            FROM carteira_raw c
            LEFT JOIN receita.estabelecimentos e ON (e.cnpj_basico || e.cnpj_ordem || e.cnpj_dv) = c.cnpj
            LEFT JOIN receita.municipios m ON e.municipio = m.codigo
            WHERE c.cnpj = ?
            LIMIT 1
        ", [$cleanCnpj])->getRowArray();

        if (!$cliente) {
            return $this->response->setJSON(['success' => true, 'redes' => [], 'error' => 'Cliente não cadastrado.']);
        }

        $nomeBusca = !empty($cliente['nome_fantasia']) ? $cliente['nome_fantasia'] : $cliente['razao_social'];
        $cidade = $cliente['municipio_nome'] ?? '';

        // ── Normalização inteligente do termo de busca (especialmente para MEIs e nomes com CNPJ) ──
        $nomeLimpo = $nomeBusca;
        
        // 1. Remove padrão de CNPJ/MEI inicial (ex: "33.525.997 " ou "33525997000152 ")
        $nomeLimpo = preg_replace('/^\d{2}\.?\d{3}\.?\d{3}\s+/', '', $nomeLimpo);
        $nomeLimpo = preg_replace('/^\d+\s+/', '', $nomeLimpo);
        
        // 2. Remove sufixos corporativos comuns do final
        $nomeLimpo = preg_replace('/\b(LTDA|S\.?A\.?|EIRELI|MEI|ME|EPP|LIMITADA|CNPJ|UNIPESSOAL)\b/i', '', $nomeLimpo);
        
        // 3. Remove traços, pontuações extras e espaços múltiplos
        $nomeLimpo = trim(preg_replace('/\s+/', ' ', $nomeLimpo));

        // Query de busca (sem aspas duplas obrigatórias para dar flexibilidade ao Google)
        $searchQuery = trim("{$nomeLimpo} {$cidade} (instagram OR linkedin OR facebook)");

        $sugestoes = [];
        $errorMsg = null;
        $isError = false;

        // ── Extração inteligente de website a partir do E-mail Corporativo da Receita ──
        if (!empty($cliente['email']) && filter_var($cliente['email'], FILTER_VALIDATE_EMAIL)) {
            $parts = explode('@', $cliente['email']);
            $domain = strtolower(trim($parts[1] ?? ''));
            
            // Provedores públicos a ignorar
            $provedoresPublicos = [
                'gmail.com', 'hotmail.com', 'yahoo.com', 'outlook.com', 'live.com', 'icloud.com',
                'uol.com.br', 'bol.com.br', 'terra.com.br', 'ig.com.br', 'globomail.com', 'oi.com.br',
                'pop.com.br', 'r7.com', 'zipmail.com.br', 'protonmail.com', 'zoho.com', 'aol.com',
                'adv.oab.org.br', 'yandex.com', 'mail.com', 'msn.com', 'gmx.com'
            ];
            
            if (!empty($domain) && !in_array($domain, $provedoresPublicos)) {
                $sugestoes[] = [
                    'cnpj'       => $cleanCnpj,
                    'network'    => 'website',
                    'url'        => 'https://www.' . $domain,
                    'status'     => 'sugestao',
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ];
            }
        }

        // Tenta obter a API key
        $apiKey = env('serper.apiKey') ?: env('SERPER_API_KEY') ?: getenv('serper.apiKey') ?: getenv('SERPER_API_KEY');

        if (!empty($apiKey)) {
            // 1. Tenta obter dados do estabelecimento via Google Local / Places (Maps)
            $searchQueryPlaces = trim("{$nomeLimpo} {$cidade}");
            $placesSugestoes = $this->querySerperPlaces($searchQueryPlaces, $cleanCnpj, $apiKey);
            if (!empty($placesSugestoes)) {
                $sugestoes = array_merge($sugestoes, $placesSugestoes);
            }

            // 2. Tenta obter os resultados orgânicos gerais
            $apiResult = $this->querySerperApi($searchQuery, $cleanCnpj, $apiKey, $nomeLimpo);
            if ($apiResult['success']) {
                $searchSugestoes = $apiResult['results'];
                if (!empty($searchSugestoes)) {
                    $sugestoes = array_merge($sugestoes, $searchSugestoes);
                }
                
                if (empty($sugestoes)) {
                    // Se a API funcionou mas não achou nada, tenta o scraping como último fallback
                    $sugestoes = $this->scrapeBing($searchQuery, $cleanCnpj);
                    if (empty($sugestoes)) {
                        $sugestoes = $this->scrapeDuckDuckGo($searchQuery, $cleanCnpj);
                    }
                }
            } else {
                $errorMsg = $apiResult['error'];
                // Se a API orgânica falhou mas temos dados do Places, não consideramos erro crítico para a UI
                if (empty($sugestoes)) {
                    $isError = true;
                }
            }
        } else {
            // Se não houver chave API cadastrada, tenta os scrapers legados diretamente
            $sugestoes = $this->scrapeBing($searchQuery, $cleanCnpj);
            if (empty($sugestoes)) {
                $sugestoes = $this->scrapeDuckDuckGo($searchQuery, $cleanCnpj);
            }
            $errorMsg = 'Para resultados automatizados mais robustos e sem captcha, configure serper.apiKey no arquivo .env.';
        }

        // ── Detecta E-commerce se tivermos um website sugerido ──
        $techSugestoes = [];
        $now = date('Y-m-d H:i:s');
        foreach ($sugestoes as $sug) {
            if ($sug['network'] === 'website') {
                $detected = $this->detectECommerce($sug['url']);
                foreach ($detected as $platform) {
                    $techSugestoes[] = [
                        'cnpj'       => $cleanCnpj,
                        'network'    => $platform,
                        'url'        => $sug['url'],
                        'status'     => 'sugestao',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }
        }
        if (!empty($techSugestoes)) {
            $sugestoes = array_merge($sugestoes, $techSugestoes);
        }

        // ── Persiste sugestões no banco ──
        $novasSugestoes = 0;
        foreach ($sugestoes as $sug) {
            try {
                $db->table('client_social_media')->insert($sug);
                $novasSugestoes++;
            } catch (\Exception $ex) {
                // Ignora duplicata pela UNIQUE KEY (cnpj, network, url)
            }
        }

        // ── Retorna TODAS as redes ativas deste CNPJ (sugestão + validadas) ──
        $redes = $db->table('client_social_media')
                    ->where('cnpj', $cleanCnpj)
                    ->where('status !=', 'rejeitado')
                    ->orderBy('status', 'DESC')
                    ->get()
                    ->getResultArray();

        $response = [
            'success' => !$isError,
            'redes'   => $redes,
        ];

        if ($errorMsg) {
            if ($isError) {
                $response['error'] = $errorMsg;
            } else {
                $response['warning'] = $errorMsg;
            }
        }

        if ($novasSugestoes > 0) {
            $response['message'] = "{$novasSugestoes} nova(s) sugestão(ões) encontrada(s)!";
        } elseif (!empty($redes)) {
            $response['message'] = count($redes) . ' rede(s) social(is) já cadastrada(s)/site(s) encontrado(s).';
        } else {
            $response['message'] = 'Nenhuma rede social ou site encontrado para esta empresa.';
        }

        return $this->response->setJSON($response);
    }

    /**
     * Tenta identificar se o website é um e-commerce e qual plataforma utiliza.
     */
    private function detectECommerce(string $url): array
    {
        $client = \Config\Services::curlrequest();
        $platforms = [];

        try {
            $response = $client->get($url, [
                'headers' => [
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
                ],
                'timeout' => 4, // timeout curto para não travar a requisição AJAX
                'http_errors' => false
            ]);

            if ($response->getStatusCode() === 200) {
                $html = $response->getBody();

                // 1. Shopify
                if (stripos($html, 'cdn.shopify.com') !== false || stripos($html, 'Shopify.theme') !== false) {
                    $platforms[] = 'shopify';
                }
                // 2. WooCommerce
                if (stripos($html, 'wp-content/plugins/woocommerce') !== false || stripos($html, 'wc-ajax') !== false) {
                    $platforms[] = 'woocommerce';
                }
                // 3. Nuvemshop
                if (stripos($html, 'nuvemshop') !== false || stripos($html, 'tiendanube') !== false) {
                    $platforms[] = 'nuvemshop';
                }
                // 4. Tray
                if (stripos($html, 'tray.com.br') !== false || stripos($html, 'tray-cdn') !== false) {
                    $platforms[] = 'tray';
                }
                // 5. VTEX
                if (stripos($html, 'vtex.js') !== false || stripos($html, 'vteximg.com.br') !== false || stripos($html, 'vtex-commerce') !== false) {
                    $platforms[] = 'vtex';
                }
            }
        } catch (\Exception $e) {
            // Silencia erros de timeout ou conexão para não quebrar a prospecção
        }

        return $platforms;
    }

    /**
     * Consulta a API do Serper.dev Places (Google Maps) para obter dados oficiais do estabelecimento.
     */
    private function querySerperPlaces(string $query, string $cnpj, string $apiKey): array
    {
        $client = \Config\Services::curlrequest();
        $sugestoes = [];
        $now = date('Y-m-d H:i:s');

        try {
            $response = $client->post('https://google.serper.dev/places', [
                'headers' => [
                    'X-API-KEY'    => $apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'q'  => $query,
                    'gl' => 'br',
                    'hl' => 'pt-br'
                ],
                'timeout' => 5, // Timeout curto
                'http_errors' => false
            ]);

            $statusCode = $response->getStatusCode();
            if ($statusCode === 200) {
                $data = json_decode($response->getBody(), true);
                if (!empty($data['places'])) {
                    $place = $data['places'][0]; // Ficha mais relevante
                    
                    // 1. Website cadastrado na ficha do Maps
                    if (!empty($place['website'])) {
                        $webUrl = filter_var($place['website'], FILTER_VALIDATE_URL);
                        if ($webUrl) {
                            $sugestoes[] = [
                                'cnpj'       => $cnpj,
                                'network'    => 'website',
                                'url'        => rtrim($webUrl, '/'),
                                'status'     => 'sugestao',
                                'created_at' => $now,
                                'updated_at' => $now,
                            ];
                        }
                    }

                    // 2. Telefone cadastrado na ficha do Maps
                    if (!empty($place['phoneNumber'])) {
                        $rawPhone = preg_replace('/\D/', '', $place['phoneNumber']);
                        if (!empty($rawPhone)) {
                            // Salva a sugestão de telefone
                            $sugestoes[] = [
                                'cnpj'       => $cnpj,
                                'network'    => 'phone',
                                'url'        => 'tel:' . $rawPhone,
                                'status'     => 'sugestao',
                                'created_at' => $now,
                                'updated_at' => $now,
                            ];
                        }
                    }
                }
            } else {
                log_message('error', "[OSINT-Places] Erro HTTP " . $statusCode);
            }
        } catch (\Exception $e) {
            log_message('error', "[OSINT-Places] Erro de rede: " . $e->getMessage());
        }

        return $sugestoes;
    }

    /**
     * Consulta a API do Serper.dev para obter resultados orgânicos do Google Search.
     */
    private function querySerperApi(string $query, string $cnpj, string $apiKey, string $nomeOriginal): array
    {
        $client = \Config\Services::curlrequest();
        $sugestoes = [];
        $now = date('Y-m-d H:i:s');
        $errorMsg = null;

        try {
            $response = $client->post('https://google.serper.dev/search', [
                'headers' => [
                    'X-API-KEY'    => $apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'q'  => $query,
                    'gl' => 'br',
                    'hl' => 'pt-br'
                ],
                'timeout' => 10,
                'http_errors' => false
            ]);

            $statusCode = $response->getStatusCode();
            if ($statusCode === 200) {
                $data = json_decode($response->getBody(), true);
                
                if (!empty($data['organic'])) {
                    // Tokeniza o nome original em palavras significativas (comprimento >= 3)
                    $termos = [];
                    $parts = explode(' ', $nomeOriginal);
                    foreach ($parts as $part) {
                        $cleanPart = trim($part);
                        if (strlen($cleanPart) >= 3) {
                            $termos[] = strtolower($cleanPart);
                        }
                    }

                    // Lista de sites agregadores a descartar na detecção de website
                    $agregadores = [
                        'instagram.com', 'facebook.com', 'linkedin.com', 'twitter.com', 'youtube.com', 'pinterest.com',
                        'cnpj.biz', 'consultasocio.com', 'serasaexperian.com.br', 'jusbrasil.com.br',
                        'econodata.com.br', 'casadosdados.com.br', 'cnpj.info', 'transparencia.cc',
                        'solutudo.com.br', 'apontador.com.br', 'guiamais.com.br', 'cadastrocnpj.com.br',
                        'qualocnpj.com', 'neoway.com.br', 'ccfacil.com.br', 'empresascnpj.com', 'infocnpj.com',
                        'informecadastral.com.br', 'cnpj.rocks', 'consultas.plus', 'registro.br'
                    ];

                    $uniqueUrls = [];
                    $websiteCount = 0;

                    foreach ($data['organic'] as $item) {
                        $url = $item['link'] ?? '';
                        if (empty($url)) {
                            continue;
                        }

                        // Identifica se é rede social clássica
                        $isSocial = preg_match('#^https?://(www\.)?(instagram\.com|linkedin\.com|facebook\.com)#i', $url);
                        
                        $network = 'website';
                        if ($isSocial) {
                            if (stripos($url, 'instagram.com') !== false) {
                                $network = 'instagram';
                            } elseif (stripos($url, 'linkedin.com') !== false) {
                                $network = 'linkedin';
                            } elseif (stripos($url, 'facebook.com') !== false) {
                                $network = 'facebook';
                            }
                        } else {
                            // Se não for rede social, avaliamos se é um website corporativo legítimo
                            $isAgregador = false;
                            foreach ($agregadores as $agregador) {
                                if (stripos($url, $agregador) !== false) {
                                    $isAgregador = true;
                                    break;
                                }
                            }
                            if ($isAgregador) {
                                continue;
                            }
                        }

                        // Validar URL
                        $url = filter_var($url, FILTER_VALIDATE_URL);
                        if (!$url) {
                            continue;
                        }

                        // Ignorar links genéricos
                        if (preg_match('/(login|signup|download|apps|settings|business|developers|policies|legal|privacy|share\b)/i', $url)) {
                            continue;
                        }

                        // Normalizar: remover query strings
                        $cleanUrl = preg_replace('/\?(utm_|fbclid|ref|hl|locale|_rdr).*$/', '', $url);
                        $cleanUrl = rtrim($cleanUrl, '/');

                        // ── Validação Léxica de URL e Título (Assertividade) ──
                        if (!empty($termos)) {
                            $urlLower = strtolower($cleanUrl);
                            $titleLower = strtolower($item['title'] ?? '');
                            $matched = false;
                            
                            foreach ($termos as $termo) {
                                if (strpos($urlLower, $termo) !== false || strpos($titleLower, $termo) !== false) {
                                    $matched = true;
                                    break;
                                }
                            }
                            
                            // Se nenhuma das palavras-chave bater com o link/título, descarta
                            if (!$matched) {
                                continue;
                            }
                        }

                        if ($network === 'website') {
                            if ($websiteCount >= 1) {
                                continue;
                            }
                            $websiteCount++;
                        }

                        if (isset($uniqueUrls[$cleanUrl])) {
                            continue;
                        }
                        $uniqueUrls[$cleanUrl] = true;

                        $sugestoes[] = [
                            'cnpj'       => $cnpj,
                            'network'    => $network,
                            'url'        => $cleanUrl,
                            'status'     => 'sugestao',
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }
                }
            } else {
                $body = json_decode($response->getBody(), true);
                $errorMsg = $body['message'] ?? 'Erro retornado pela API do Serper (HTTP ' . $statusCode . ')';
                log_message('error', "[OSINT-Serper] Erro HTTP " . $statusCode . ": " . $errorMsg);
            }
        } catch (\Exception $e) {
            $errorMsg = 'Falha de conexão com a API do Serper: ' . $e->getMessage();
            log_message('error', "[OSINT-Serper] Erro de rede: " . $e->getMessage());
        }

        return [
            'success' => $errorMsg === null,
            'results' => $sugestoes,
            'error'   => $errorMsg
        ];
    }

    /**
     * Scrape Bing HTML search para encontrar links de redes sociais.
     */
    private function scrapeBing(string $query, string $cnpj): array
    {
        $client = \Config\Services::curlrequest();
        $sugestoes = [];

        try {
            $response = $client->get('https://www.bing.com/search', [
                'headers' => [
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                    'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                    'Accept-Language' => 'pt-BR,pt;q=0.9,en;q=0.8',
                ],
                'query' => [
                    'q' => $query,
                    'setlang' => 'pt-br',
                    'cc' => 'br',
                ],
                'timeout' => 8,
                'http_errors' => false,
            ]);

            if ($response->getStatusCode() === 200) {
                $html = $response->getBody();
                $sugestoes = $this->extractSocialLinks($html, $cnpj);
            }
        } catch (\Exception $e) {
            log_message('error', "[OSINT-Bing] Erro: " . $e->getMessage());
        }

        return $sugestoes;
    }

    /**
     * Scrape DuckDuckGo HTML search como fallback.
     */
    private function scrapeDuckDuckGo(string $query, string $cnpj): array
    {
        $client = \Config\Services::curlrequest();
        $sugestoes = [];

        try {
            $response = $client->get('https://html.duckduckgo.com/html/', [
                'headers' => [
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                    'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                    'Accept-Language' => 'pt-BR,pt;q=0.9,en;q=0.8',
                ],
                'query' => [
                    'q' => $query,
                ],
                'timeout' => 8,
                'http_errors' => false,
            ]);

            if ($response->getStatusCode() === 200) {
                $html = $response->getBody();

                // DuckDuckGo usa redirect via uddg=
                $sugestoes = $this->extractSocialLinks($html, $cnpj, true);
            }
        } catch (\Exception $e) {
            log_message('error', "[OSINT-DDG] Erro: " . $e->getMessage());
        }

        return $sugestoes;
    }

    /**
     * Extrai links de redes sociais do HTML, filtra e normaliza.
     *
     * @param string $html          Conteúdo HTML da página de resultados
     * @param string $cnpj          CNPJ para associar às sugestões
     * @param bool   $decodeDdg     Se true, tenta decodificar URLs do DuckDuckGo (uddg=)
     * @return array
     */
    private function extractSocialLinks(string $html, string $cnpj, bool $decodeDdg = false): array
    {
        $sugestoes = [];
        $now = date('Y-m-d H:i:s');

        // Regex para encontrar links
        preg_match_all('/<a[^>]*href="([^"]*)"[^>]*>/i', $html, $anchorMatches);

        if (empty($anchorMatches[1])) {
            return $sugestoes;
        }

        $uniqueUrls = [];

        foreach ($anchorMatches[1] as $href) {
            $url = $href;

            // Decodificar redirect do DuckDuckGo
            if ($decodeDdg && strpos($url, 'uddg=') !== false) {
                parse_str(parse_url($url, PHP_URL_QUERY), $queryParts);
                if (!empty($queryParts['uddg'])) {
                    $url = $queryParts['uddg'];
                }
            }

            // Só nos interessam URLs absolutas de redes sociais
            if (!preg_match('#^https?://(www\.)?(instagram\.com|linkedin\.com|facebook\.com)#i', $url)) {
                continue;
            }

            // Validar URL
            $url = filter_var($url, FILTER_VALIDATE_URL);
            if (!$url) {
                continue;
            }

            // Ignorar links genéricos
            if (preg_match('/(login|signup|download|apps|settings|business|developers|policies|legal|privacy|share\b)/i', $url)) {
                continue;
            }

            // Normalizar: remover query strings irrelevantes (utm_, fbclid, etc.)
            $cleanUrl = preg_replace('/\?(utm_|fbclid|ref|hl|locale|_rdr).*$/', '', $url);
            // Remover trailing slash
            $cleanUrl = rtrim($cleanUrl, '/');

            if (isset($uniqueUrls[$cleanUrl])) {
                continue;
            }
            $uniqueUrls[$cleanUrl] = true;

            // Identificar rede social
            $network = 'website';
            if (stripos($cleanUrl, 'instagram.com') !== false) {
                $network = 'instagram';
            } elseif (stripos($cleanUrl, 'linkedin.com') !== false) {
                $network = 'linkedin';
            } elseif (stripos($cleanUrl, 'facebook.com') !== false) {
                $network = 'facebook';
            }

            $sugestoes[] = [
                'cnpj'       => $cnpj,
                'network'    => $network,
                'url'        => $cleanUrl,
                'status'     => 'sugestao',
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        return $sugestoes;
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

    // ─── Scanner Reclame Aqui OSINT (Fase 3.5)

    /**
     * Resolve a chave Serper do usuário logado.
     * Prioridade: chave pessoal (vendor_users.serper_api_key) > chave global (.env)
     */
    private function getSerperKey(): string
    {
        $vendorUser = $this->getVendorUser();
        if ($vendorUser && !empty($vendorUser['serper_api_key'])) {
            return $vendorUser['serper_api_key'];
        }
        return env('serper.apiKey') ?: env('SERPER_API_KEY') ?: getenv('serper.apiKey') ?: getenv('SERPER_API_KEY') ?: '';
    }

    /**
     * POST /vendedor/serper-key — Salva a chave Serper pessoal do usuário via AJAX.
     */
    public function serperKeySalvar()
    {
        $vendorUser = $this->getVendorUser();
        if (!$vendorUser) {
            return $this->response->setJSON(['error' => 'Não autorizado'])->setStatusCode(403);
        }

        $apiKey = trim($this->request->getPost('serper_api_key') ?? '');

        if (empty($apiKey)) {
            // Permite apagar a chave
            db_connect()->table('vendor_users')
                ->where('matricula', $vendorUser['matricula'])
                ->update(['serper_api_key' => null, 'updated_at' => date('Y-m-d H:i:s')]);
            return $this->response->setJSON(['success' => true, 'message' => 'Chave removida.']);
        }

        // Validação mínima: chaves Serper têm 40+ caracteres
        if (strlen($apiKey) < 20) {
            return $this->response->setJSON(['error' => 'Chave inválida. Verifique se copiou corretamente.']);
        }

        db_connect()->table('vendor_users')
            ->where('matricula', $vendorUser['matricula'])
            ->update(['serper_api_key' => $apiKey, 'updated_at' => date('Y-m-d H:i:s')]);

        return $this->response->setJSON(['success' => true, 'message' => 'Chave Serper salva com sucesso!']);
    }

    public function reclameAquiScan(string $cnpj)
    {
        $vendorUser = $this->getVendorUser();
        if (!$vendorUser) {
            return $this->response->setJSON(['error' => 'Não autorizado'])->setStatusCode(403);
        }

        $cnpjClean   = preg_replace('/[^0-9]/', '', $cnpj);
        $forceRefresh = (bool) $this->request->getPost('force_refresh');
        $db          = db_connect();
        $agora       = date('Y-m-d H:i:s');
        $userId      = auth()->user()->id ?? null;

        // ── 1. Verifica cache (válido por 30 dias, a não ser que force_refresh) ──
        if (!$forceRefresh) {
            $cache = $db->table('client_ra_scans')
                        ->where('cnpj', $cnpjClean)
                        ->get()->getRowArray();

            if ($cache && !empty($cache['pesquisado_em'])) {
                $diasDesde = (time() - strtotime($cache['pesquisado_em'])) / 86400;
                if ($diasDesde < 30) {
                    $resultados = json_decode($cache['resultado_json'] ?? '[]', true) ?: [];
                    return $this->response->setJSON([
                        'success'       => true,
                        'empresa'       => $cache['empresa_nome'],
                        'resultados'    => $resultados,
                        'is_cache'      => true,
                        'cache_status'  => $cache['status'],
                        'cache_total'   => (int) $cache['total'],
                        'pesquisado_em' => $cache['pesquisado_em'],
                        'pesquisado_por_id' => $cache['pesquisado_por'],
                    ]);
                }
            }
        }

        // ── 2. Busca nome na base da Receita ──────────────────────────────────
        $empresa = $db->query("
            SELECT COALESCE(e.nome_fantasia, emp.razao_social) AS nome_busca
            FROM receita.estabelecimentos e
            LEFT JOIN receita.empresas emp ON emp.cnpj_basico = e.cnpj_basico
            WHERE (e.cnpj_basico || e.cnpj_ordem || e.cnpj_dv) = ?
            LIMIT 1
        ", [$cnpjClean])->getRowArray();

        if (!$empresa || empty($empresa['nome_busca'])) {
            return $this->response->setJSON(['error' => 'CNPJ não encontrado na base de dados local.'])->setStatusCode(404);
        }

        // ── 3. Verifica chave Serper ──────────────────────────────────────────
        $userKey = !empty($vendorUser['serper_api_key']) ? $vendorUser['serper_api_key'] : null;
        $scanner = new \App\Services\ReclameAquiScanner($userKey);

        if (!$scanner->hasKey()) {
            return $this->response->setJSON([
                'error'      => 'NO_API_KEY',
                'error_type' => 'NO_API_KEY',
            ])->setStatusCode(402);
        }

        // ── 4. Executa o scan ─────────────────────────────────────────────────
        $resultados = $scanner->scan($empresa['nome_busca']);

        if (isset($resultados['error'])) {
            $isNoKey = in_array($resultados['error_type'] ?? '', ['NO_API_KEY', 'INVALID_KEY']);
            return $this->response->setJSON([
                'error'      => $resultados['error'],
                'error_type' => $resultados['error_type'] ?? 'SCAN_ERROR',
            ])->setStatusCode($isNoKey ? 402 : 500);
        }

        // ── 5. Persiste resultado no cache ────────────────────────────────────
        $total  = count($resultados);
        $status = $total > 0 ? 'encontrado' : 'nao_encontrado';

        $cacheRow = [
            'empresa_nome'   => $empresa['nome_busca'],
            'status'         => $status,
            'total'          => $total,
            'resultado_json' => json_encode($resultados, JSON_UNESCAPED_UNICODE),
            'pesquisado_por' => $userId,
            'pesquisado_em'  => $agora,
        ];

        $exists = $db->table('client_ra_scans')->where('cnpj', $cnpjClean)->countAllResults();
        if ($exists) {
            $db->table('client_ra_scans')->where('cnpj', $cnpjClean)->update($cacheRow);
        } else {
            $cacheRow['cnpj'] = $cnpjClean;
            $db->table('client_ra_scans')->insert($cacheRow);
        }

        return $this->response->setJSON([
            'success'       => true,
            'empresa'       => $empresa['nome_busca'],
            'resultados'    => $resultados,
            'is_cache'      => false,
            'cache_status'  => $status,
            'cache_total'   => $total,
            'pesquisado_em' => $agora,
            'pesquisado_por_id' => $userId,
        ]);
    }

    /**
     * Retorna os CNAEs (Principal e Secundários) com seus códigos e descrições textuais para 1 CNPJ.
     */
    public function getCnaesDetalhados(string $cnpj): array
    {
        $cleanCnpj = preg_replace('/[^0-9]/', '', $cnpj);
        $res = $this->getBulkCnaesDetalhados([$cleanCnpj]);
        return $res[$cleanCnpj] ?? [];
    }

    /**
     * Retorna os CNAEs (Principal e Secundários) com seus códigos e descrições textuais em lote para N CNPJs.
     */
    public function getBulkCnaesDetalhados(array $cnpjs): array
    {
        if (empty($cnpjs)) return [];

        $cleanCnpjs = array_map(fn($c) => preg_replace('/[^0-9]/', '', (string)$c), $cnpjs);
        $cleanCnpjs = array_values(array_unique(array_filter($cleanCnpjs)));
        if (empty($cleanCnpjs)) return [];

        $db = db_connect();
        $placeholders = implode(',', array_fill(0, count($cleanCnpjs), '?'));
        $estRows = $db->query("
            SELECT (cnpj_basico || cnpj_ordem || cnpj_dv) AS cnpj,
                   cnae_fiscal_principal, cnae_fiscal_secundaria
            FROM receita.estabelecimentos
            WHERE (cnpj_basico || cnpj_ordem || cnpj_dv) IN ({$placeholders})
        ", $cleanCnpjs)->getResultArray();

        $allCodes = [];
        $cnpjCnaes = [];

        foreach ($estRows as $row) {
            $cnpj = $row['cnpj'];
            $principal = !empty($row['cnae_fiscal_principal']) ? trim($row['cnae_fiscal_principal']) : null;
            $items = [];

            if ($principal) {
                $items[] = ['codigo' => $principal, 'tipo' => 'Principal', 'descricao' => '—'];
                $allCodes[] = $principal;
            }

            if (!empty($row['cnae_fiscal_secundaria'])) {
                $secs = explode(',', $row['cnae_fiscal_secundaria']);
                foreach ($secs as $s) {
                    $code = trim($s);
                    if (!empty($code) && $code !== $principal) {
                        $already = false;
                        foreach ($items as $it) {
                            if ($it['codigo'] === $code) { $already = true; break; }
                        }
                        if (!$already) {
                            $items[] = ['codigo' => $code, 'tipo' => 'Secundário', 'descricao' => '—'];
                            $allCodes[] = $code;
                        }
                    }
                }
            }
            $cnpjCnaes[$cnpj] = $items;
        }

        $allCodes = array_values(array_unique(array_filter($allCodes)));
        if (empty($allCodes)) return $cnpjCnaes;

        $placeholdersCodes = implode(',', array_fill(0, count($allCodes), '?'));
        $cnaeDescs = $db->query("
            SELECT codigo, descricao
            FROM receita.cnaes
            WHERE codigo IN ({$placeholdersCodes})
        ", $allCodes)->getResultArray();

        $map = [];
        foreach ($cnaeDescs as $cd) {
            $map[trim($cd['codigo'])] = trim($cd['descricao']);
        }

        foreach ($cnpjCnaes as $cnpj => &$items) {
            foreach ($items as &$it) {
                if (isset($map[$it['codigo']])) {
                    $it['descricao'] = $map[$it['codigo']];
                }
            }
        }

        return $cnpjCnaes;
    }
}
