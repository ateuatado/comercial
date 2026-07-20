<?php

namespace App\Controllers;

/**
 * Controller: Admin
 * Dashboard administrativo sumarizado.
 */
class AdminController extends BaseController
{
    public function dashboard(): string
    {
        $db = db_connect();

        // ── KPIs da carteira_raw (base principal) ──
        $totalCarteira = (int) ($db->query("SELECT COUNT(*) AS c FROM carteira_raw")->getRow()->c ?? 0);
        $totalVendedores = (int) ($db->query("SELECT COUNT(*) AS c FROM vendor_users WHERE ativo = true")->getRow()->c ?? 0);
        $totalSEs = (int) ($db->query("SELECT COUNT(DISTINCT se) AS c FROM carteira_raw WHERE se IS NOT NULL")->getRow()->c ?? 0);
        $totalSegmentos = (int) ($db->query("SELECT COUNT(DISTINCT segmento_mercado) AS c FROM carteira_raw WHERE segmento_mercado IS NOT NULL")->getRow()->c ?? 0);

        // ── Última importação ──
        $ultimaImport = $db->query("SELECT created_at, inserted, status FROM import_logs WHERE status = 'concluido' ORDER BY created_at DESC LIMIT 1")->getRow();

        // ── Distribuição por Categoria (para gráfico doughnut) ──
        $categorias = $db->query("
            SELECT categoria, COUNT(*) AS total
            FROM carteira_raw
            WHERE categoria IS NOT NULL AND categoria != ''
            GROUP BY categoria
            ORDER BY total DESC
        ")->getResultArray();

        // ── Top 10 SEs (para gráfico barras) ──
        $topSEs = $db->query("
            SELECT se, COUNT(*) AS total
            FROM carteira_raw
            WHERE se IS NOT NULL
            GROUP BY se
            ORDER BY total DESC
            LIMIT 10
        ")->getResultArray();

        // ── Distribuição por Ciclo de Vida ──
        $ciclos = $db->query("
            SELECT ciclo_de_vida, COUNT(*) AS total
            FROM carteira_raw
            WHERE ciclo_de_vida IS NOT NULL AND ciclo_de_vida != ''
            GROUP BY ciclo_de_vida
            ORDER BY total DESC
        ")->getResultArray();

        // ── Notas recentes (atividade do sistema) ──
        $notasRecentes = (int) ($db->query("SELECT COUNT(*) AS c FROM vendor_notes WHERE created_at > NOW() - INTERVAL '7 days'")->getRow()->c ?? 0);

        return view('admin/dashboard', [
            'totalCarteira'   => $totalCarteira,
            'totalVendedores' => $totalVendedores,
            'totalSEs'        => $totalSEs,
            'totalSegmentos'  => $totalSegmentos,
            'ultimaImport'    => $ultimaImport,
            'categorias'      => $categorias,
            'topSEs'          => $topSEs,
            'ciclos'          => $ciclos,
            'notasRecentes'   => $notasRecentes,
        ]);
    }

    /**
     * Exibe o histórico completo de movimentações da carteira com paginação.
     */
    public function historicalMovements(): string
    {
        $db      = db_connect();
        $perPage = 50;
        $page    = max(1, (int) $this->request->getGet('page'));
        $offset  = ($page - 1) * $perPage;

        $se = $this->getAdminSE();
        $seConditionMovement = $se ? "WHERE vendor_id_novo IN (SELECT id FROM vendors WHERE estado_se = " . $db->escape($se) . ") OR vendor_id_anterior IN (SELECT id FROM vendors WHERE estado_se = " . $db->escape($se) . ")" : "";

        $total = (int) ($db->query("
            SELECT COUNT(id) AS total FROM wallet_movements
            {$seConditionMovement}
        ")->getRow()->total ?? 0);

        $historico = $db->query("
            SELECT
                wm.id,
                wm.cnpj,
                wm.vendor_id_anterior,
                wm.vendor_id_novo,
                wm.tipo_movimento,
                wm.realizado_por,
                wm.created_at,
                v_ant.nome AS vendor_anterior,
                v_novo.nome AS vendor_novo,
                e.razao_social
            FROM wallet_movements wm
            LEFT JOIN vendors v_ant ON v_ant.id = wm.vendor_id_anterior
            LEFT JOIN vendors v_novo ON v_novo.id = wm.vendor_id_novo
            LEFT JOIN receita.empresas e ON e.cnpj_basico = SUBSTRING(wm.cnpj, 1, 8)
            ORDER BY wm.created_at DESC
            LIMIT {$perPage} OFFSET {$offset}
        ")->getResultArray();

        $totalPages = $total > 0 ? (int) ceil($total / $perPage) : 1;

        return view('admin/historical', [
            'page_title'  => 'Histórico de Movimentações',
            'historico'   => $historico,
            'page'        => $page,
            'total_pages' => $totalPages,
            'total'       => $total,
            'per_page'    => $perPage,
        ]);
    }

    // ─── Gestão Manual de Localizações (Fase 2.6b) ────────────────

    public function localizacaoManual()
    {
        $db = db_connect();
        $busca = $this->request->getGet('busca');
        $clientes = [];

        if (!empty($busca)) {
            // Limita a busca em 50 para evitar travamento da listagem
            $sql = "SELECT c.cnpj, c.razao_social, 
                           e.tipo_logradouro, e.logradouro, e.numero, e.bairro, e.municipio AS municipio_codigo, e.uf,
                           cl.latitude, cl.longitude
                    FROM carteira_raw c
                    LEFT JOIN client_locations cl ON cl.cnpj = c.cnpj
                    LEFT JOIN receita.estabelecimentos e ON (e.cnpj_basico || e.cnpj_ordem || e.cnpj_dv) = c.cnpj
                    WHERE c.cnpj LIKE ? OR LOWER(c.razao_social) LIKE LOWER(?)
                    ORDER BY c.razao_social ASC
                    LIMIT 50";
            
            $clientes = $db->query($sql, ["%{$busca}%", "%{$busca}%"])->getResultArray();

            // Resolver nome dos municípios
            foreach ($clientes as &$c) {
                $c['municipio_nome'] = '';
                if ($c['municipio_codigo']) {
                    $mun = $db->table('receita.municipios')
                              ->select('descricao')
                              ->where('codigo', $c['municipio_codigo'])
                              ->get()
                              ->getRowArray();
                    if ($mun) {
                        $c['municipio_nome'] = $mun['descricao'];
                    }
                }
            }
        }

        return view('admin/localizacao', compact('clientes', 'busca'));
    }

    // ─── Scoring Preditivo (Fase 3) ────────────────────────────────

    /**
     * Painel de configuração de pesos do Score Preditivo de leads logísticos.
     */
    public function scoringConfig(): string
    {
        $db = db_connect();

        // Ler todas as configurações de peso
        $rows = $db->query("SELECT key, value FROM scoring_config")->getResultArray();
        $config = [];
        foreach ($rows as $row) {
            $config[$row['key']] = $row['value'];
        }

        // Ler regras de CNAE ordenadas por peso desc
        $cnaeRules = $db->query("SELECT cnae_code, weight, description FROM cnae_scoring_rules ORDER BY weight DESC, cnae_code ASC")->getResultArray();

        // Verificar se há recálculo em andamento
        $cache       = \Config\Services::cache();
        $progress    = $cache->get('scoring_recalculation_progress');
        $recalculating = ($progress !== null && $progress < 100);

        return view('admin/scoring_config', [
            'config'        => $config,
            'cnaeRules'     => $cnaeRules,
            'recalculating' => $recalculating,
            'flash_success' => session()->getFlashdata('scoring_success'),
        ]);
    }

    /**
     * Persiste os pesos alterados pelo administrador.
     */
    public function scoringSalvar()
    {
        $db = db_connect();
        $now = date('Y-m-d H:i:s');

        $fields = ['weight_cnae', 'weight_capital', 'weight_email', 'weight_nome_fantasia', 'weight_localizacao', 'amortization_factor', 'capital_tier_high', 'capital_tier_mid'];

        foreach ($fields as $field) {
            $value = $this->request->getPost($field);
            if ($value !== null) {
                $db->query(
                    "INSERT INTO scoring_config (key, value, updated_at, created_at)
                     VALUES (?, ?, ?, ?)
                     ON CONFLICT (key) DO UPDATE SET value = EXCLUDED.value, updated_at = NOW()",
                    [$field, (string) $value, $now, $now]
                );
            }
        }

        // Verificar se o parâmetro de recalcular foi passado
        if ($this->request->getPost('recalculate') === '1') {
            // Iniciar recálculo em background (Windows-compatible)
            $phpPath  = PHP_BINARY;
            $sparkPath = ROOTPATH . 'spark';
            pclose(popen("start /B {$phpPath} {$sparkPath} enrich:recalculate", "r"));

            // Inicializar o progresso no cache
            \Config\Services::cache()->save('scoring_recalculation_progress', 0, 3600);
        }

        session()->setFlashdata('scoring_success', 'Configurações salvas com sucesso!');
        return redirect()->to(site_url('admin/scoring'));
    }

    /**
     * Dispara o recálculo em background via POST AJAX.
     */
    public function scoringRecalcular()
    {
        $phpPath  = PHP_BINARY;
        $sparkPath = ROOTPATH . 'spark';
        pclose(popen("start /B {$phpPath} {$sparkPath} enrich:recalculate", "r"));
        \Config\Services::cache()->save('scoring_recalculation_progress', 0, 3600);

        return $this->response->setJSON(['success' => true, 'message' => 'Recálculo iniciado em background.']);
    }

    /**
     * Retorna o percentual de progresso do recálculo (polling AJAX).
     */
    public function scoringProgresso()
    {
        $progress = \Config\Services::cache()->get('scoring_recalculation_progress');
        return $this->response->setJSON(['progresso' => (int) ($progress ?? 0)]);
    }

    /**
     * Adiciona uma nova regra de CNAE via AJAX.
     */
    public function cnaeAdicionar()
    {
        $input  = json_decode($this->request->getBody(), true);
        $code   = trim($input['cnae_code'] ?? '');
        $desc   = trim($input['description'] ?? '');
        $weight = (int) ($input['weight'] ?? 0);
        $now    = date('Y-m-d H:i:s');

        if (empty($code) || $weight < 0 || $weight > 100) {
            return $this->response->setJSON(['error' => 'Dados inválidos.'])->setStatusCode(422);
        }

        $db = db_connect();
        $db->query(
            "INSERT INTO cnae_scoring_rules (cnae_code, weight, description, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?)
             ON CONFLICT (cnae_code) DO UPDATE SET weight = EXCLUDED.weight, description = EXCLUDED.description, updated_at = NOW()",
            [$code, $weight, $desc, $now, $now]
        );

        return $this->response->setJSON(['success' => true]);
    }

    /**
     * Remove uma regra de CNAE via AJAX.
     */
    public function cnaeRemover()
    {
        $input = json_decode($this->request->getBody(), true);
        $code  = trim($input['cnae_code'] ?? '');

        if (empty($code)) {
            return $this->response->setJSON(['error' => 'Código CNAE não informado.'])->setStatusCode(422);
        }

        db_connect()->query("DELETE FROM cnae_scoring_rules WHERE cnae_code = ?", [$code]);

        return $this->response->setJSON(['success' => true]);
    }

    // ─── Pedidos de Captação (PR-CAP) ────────────────────────────

    /**
     * Lista todos os Pedidos de Captação para o admin / coordenador.
     */
    public function captacoesIndex()
    {
        $db = db_connect();
        $filtroStatus = $this->request->getGet('status') ?? '';

        $sql = "
            SELECT cr.*,
                   COALESCE(emp.razao_social, cr.cnpj) AS razao_social,
                   COALESCE(ce.logistics_score, 0) AS score,
                   vu.nome AS nome_vendedor
            FROM captacao_requests cr
            LEFT JOIN receita.empresas emp ON emp.cnpj_basico = SUBSTRING(cr.cnpj, 1, 8)
            LEFT JOIN client_enrichment ce ON ce.cnpj = cr.cnpj
            LEFT JOIN vendor_users vu ON vu.matricula = cr.matricula
            WHERE 1=1
        ";
        $params = [];
        if ($filtroStatus) { $sql .= " AND cr.status = ?"; $params[] = $filtroStatus; }
        $sql .= " ORDER BY CASE cr.status WHEN 'pendente' THEN 0 WHEN 'mais_info' THEN 1 ELSE 2 END, cr.created_at DESC";

        $pedidos = $db->query($sql, $params)->getResultArray();

        // Contagens para abas
        $contagens = $db->query("
            SELECT status, COUNT(*) AS total FROM captacao_requests GROUP BY status
        ")->getResultArray();
        $totais = array_column($contagens, 'total', 'status');

        return view('admin/captacoes_index', compact('pedidos', 'filtroStatus', 'totais'));
    }

    /**
     * Exibe o detalhe completo de um PR-CAP para decisão administrativa.
     */
    public function captacaoDetalhe(int $id)
    {
        $db = db_connect();

        $pedido = $db->query("
            SELECT cr.*,
                   COALESCE(emp.razao_social, cr.cnpj) AS razao_social,
                   vu.nome AS nome_vendedor, vu.se AS se_vendedor
            FROM captacao_requests cr
            LEFT JOIN receita.empresas emp ON emp.cnpj_basico = SUBSTRING(cr.cnpj, 1, 8)
            LEFT JOIN vendor_users vu ON vu.matricula = cr.matricula
            WHERE cr.id = ?
        ", [$id])->getRowArray();

        if (!$pedido) return redirect()->to(site_url('admin/captacoes'))->with('error', 'Pedido não encontrado.');

        $cnpj = $pedido['cnpj'];

        // Dados completos da Receita
        $receita = $db->query("
            SELECT e.*, emp.razao_social, m.descricao AS municipio_nome,
                   sit.descricao AS situacao_desc, emp.capital_social
            FROM receita.estabelecimentos e
            LEFT JOIN receita.empresas emp ON emp.cnpj_basico = e.cnpj_basico
            LEFT JOIN receita.municipios m ON m.codigo = e.municipio
            LEFT JOIN receita.situacoes_cadastrais sit ON sit.codigo = e.situacao_cadastral
            WHERE (e.cnpj_basico || e.cnpj_ordem || e.cnpj_dv) = ?
            LIMIT 1
        ", [$cnpj])->getRowArray();

        // Score e breakdown
        $enrichment = $db->table('client_enrichment')->where('cnpj', $cnpj)->get()->getRowArray();

        // Evidências do sistema — timeline
        $locLog    = $db->table('client_locations')->where('cnpj', $cnpj)->get()->getRowArray();
        $walletLog = $db->table('client_wallets')->where('cnpj', $cnpj)->get()->getRowArray();
        $socialLog = $db->query("SELECT MAX(updated_at) AS dt FROM client_social_media WHERE cnpj = ?", [$cnpj])->getRowArray();
        $notas     = $db->query("
            SELECT vn.*, vu.nome AS nome_autor
            FROM vendor_notes vn
            LEFT JOIN vendor_users vu ON vu.id = vn.vendor_id
            WHERE vn.cnpj = ? ORDER BY vn.created_at DESC LIMIT 10
        ", [$cnpj])->getResultArray();

        // Carteira atual (se houver)
        $carteiraAtual = $db->query("
            SELECT cr2.matricula_mcmcu, cr2.forca_vendas_nome, cr2.categoria, cr2.ciclo_de_vida
            FROM carteira_raw cr2 WHERE cr2.cnpj = ?
            LIMIT 1
        ", [$cnpj])->getRowArray();

        return view('admin/captacao_detalhe', compact(
            'pedido', 'receita', 'enrichment',
            'locLog', 'walletLog', 'socialLog', 'notas',
            'carteiraAtual'
        ));
    }

    /**
     * Processa a decisão do admin sobre um PR-CAP.
     * Status possíveis: aprovar → 'aprovado', rejeitar → 'rejeitado', mais_info → 'mais_info'.
     * Se aprovado: insere/transfere o CNPJ na carteira do vendedor solicitante.
     */
    public function captacaoDecisao()
    {
        $id       = (int) $this->request->getPost('id');
        $decisao  = $this->request->getPost('decisao'); // aprovar | rejeitar | mais_info
        $obs      = trim($this->request->getPost('admin_obs') ?? '');
        $adminUser= auth()->user();

        if (!in_array($decisao, ['aprovar', 'rejeitar', 'mais_info'])) {
            return redirect()->back()->with('error', 'Decisão inválida.');
        }
        if (in_array($decisao, ['rejeitar', 'mais_info']) && empty($obs)) {
            return redirect()->back()->with('error', 'Observação obrigatória para rejeitar ou pedir mais informações.');
        }

        $db = db_connect();
        $pedido = $db->table('captacao_requests')->where('id', $id)->get()->getRowArray();
        if (!$pedido) return redirect()->to(site_url('admin/captacoes'))->with('error', 'Pedido não encontrado.');

        $statusMap = ['aprovar' => 'aprovado', 'rejeitar' => 'rejeitado', 'mais_info' => 'mais_info'];
        $novoStatus = $statusMap[$decisao];

        $updateData = [
            'status'         => $novoStatus,
            'admin_obs'      => $obs ?: null,
            'respondido_por' => $adminUser ? $adminUser->username : 'admin',
            'updated_at'     => date('Y-m-d H:i:s'),
            'decided_at'     => date('Y-m-d H:i:s'),
        ];

        if ($decisao === 'mais_info') {
            $updateData['decided_at'] = null; // não é decisão final
        }

        $db->table('captacao_requests')->where('id', $id)->update($updateData);

        // ── Se APROVADO → inserir/transferir na carteira ─────────
        if ($novoStatus === 'aprovado') {
            $cnpj = $pedido['cnpj'];
            $mat  = $pedido['matricula'];

            // Busca dados do vendedor solicitante
            $vendor = $db->table('vendor_users')->where('matricula', $mat)->get()->getRowArray();

            // Remove de outra carteira se necessário (evita duplicidade)
            $db->query("DELETE FROM carteira_raw WHERE cnpj = ? AND matricula_mcmcu != ?", [$cnpj, $mat]);

            // Verifica se o CNPJ já está na carteira do vendedor solicitante (improvável, mas seguro)
            $existe = $db->table('carteira_raw')
                ->where('cnpj', $cnpj)->where('matricula_mcmcu', $mat)->countAllResults();

            if (!$existe) {
                // Busca dados da Receita para preencher carteira_raw
                $est = $db->query("
                    SELECT e.*, emp.razao_social
                    FROM receita.estabelecimentos e
                    LEFT JOIN receita.empresas emp ON e.cnpj_basico = emp.cnpj_basico
                    WHERE (e.cnpj_basico || e.cnpj_ordem || e.cnpj_dv) = ?
                    LIMIT 1
                ", [$cnpj])->getRowArray();

                $db->table('carteira_raw')->insert([
                    'se'              => ($est['uf'] ?? 'SP') === 'SP' ? 'SPM' : 'SPI',
                    'categoria'       => 'BRONZE',
                    'cnpj'            => $cnpj,
                    'razao_social'    => $est['razao_social'] ?? 'CLIENTE',
                    'matricula_mcmcu' => $mat,
                    'forca_vendas_nome'=> $vendor['nome'] ?? $mat,
                    'ciclo_de_vida'   => 'Ativo',
                    'cnae'            => $est['cnae_fiscal_principal'] ?? null,
                    'created_at'      => date('Y-m-d H:i:s'),
                ]);
            }

            // Atualiza ou cria client_wallets
            $existsWallet = $db->table('client_wallets')->where('cnpj', $cnpj)->get()->getRow();
            $vendorId = $vendor['id'] ?? null;
            if (!$existsWallet) {
                $db->table('client_wallets')->insert([
                    'cnpj'               => $cnpj,
                    'vendor_id'          => $vendorId,
                    'status_operacional' => 'novo',
                    'created_at'         => date('Y-m-d H:i:s'),
                    'updated_at'         => date('Y-m-d H:i:s'),
                ]);
            } else {
                $db->table('client_wallets')->where('cnpj', $cnpj)->update([
                    'vendor_id'  => $vendorId,
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
            }
        }

        $msgs = [
            'aprovado'  => '✅ Cliente adicionado à carteira do vendedor.',
            'rejeitado' => '❌ Pedido rejeitado.',
            'mais_info' => '🔵 Solicitação de mais informações enviada ao vendedor.',
        ];
        return redirect()->to(site_url('admin/captacoes'))->with('success', $msgs[$novoStatus]);
    }
}

