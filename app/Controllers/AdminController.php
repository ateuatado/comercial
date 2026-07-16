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
            $sql = "SELECT c.cnpj, c.razao_social, c.logradouro, c.numero, c.bairro,
                           c.municipio_codigo, c.uf, cl.latitude, cl.longitude
                    FROM carteira_raw c
                    LEFT JOIN client_locations cl ON cl.cnpj = c.cnpj
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

    public function localizacaoManualSalvar()
    {
        $cnpj = $this->request->getPost('cnpj');
        $lat  = $this->request->getPost('lat');
        $lng  = $this->request->getPost('lng');

        if (empty($cnpj) || $lat === '' || $lng === '') {
            return $this->response->setJSON(['error' => 'Dados incompletos.'])->setStatusCode(422);
        }

        $locationModel = new \App\Models\ClientLocationModel();

        $salvo = $locationModel->upsert([
            'cnpj'               => $cnpj,
            'latitude'           => (float)$lat,
            'longitude'          => (float)$lng,
            'endereco_formatado' => 'Cadastro Manual Admin',
            'registrado_por'     => 'admin'
        ]);

        if ($salvo) {
            return $this->response->setJSON(['success' => true]);
        }

        return $this->response->setJSON(['error' => 'Falha ao salvar no banco.'])->setStatusCode(500);
    }
}
