<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Controller: Admin\SearchController
 * 
 * Permite aos administradores pesquisarem e consultarem o cadastro completo
 * de empresas e sócios diretamente no schema 'receita' da Receita Federal.
 */
class SearchController extends BaseController
{
    /**
     * Exibe a página de busca e os resultados filtrados.
     */
    public function index(): string
    {
        $db = db_connect();
        
        $q = trim($this->request->getGet('q') ?? '');
        $results = [];
        $searchType = null;
        
        $page = max(1, (int) ($this->request->getGet('page') ?? 1));
        $limit = 50;
        $offset = ($page - 1) * $limit;

        if ($q !== '') {
            // Limpa caracteres especiais da query de busca
            $cleanQ = preg_replace('/[^a-zA-Z0-9]/', '', $q);

            if (is_numeric($cleanQ)) {
                $len = strlen($cleanQ);
                if ($len === 8 || $len === 14) {
                    // Busca por CNPJ (retorna apenas 1 registro)
                    $searchType = 'cnpj';
                    $cnpjBasico = substr($cleanQ, 0, 8);
                    
                    $results = $db->query("
                        SELECT 
                            e.cnpj_basico,
                            e.razao_social,
                            e.capital_social,
                            e.porte_empresa,
                            (
                                SELECT COUNT(*) 
                                FROM client_wallets cw 
                                WHERE cw.cnpj >= (e.cnpj_basico || '000000') AND cw.cnpj <= (e.cnpj_basico || '999999')
                            ) AS em_carteira_qtd
                        FROM receita.empresas e
                        WHERE e.cnpj_basico = ?
                    ", [$cnpjBasico])->getResultArray();
                    
                } elseif ($len === 11) {
                    // Busca por CPF do Sócio
                    $searchType = 'cpf';
                    
                    // Encontra empresas que este sócio participa (com paginação limite + 1)
                    $results = $db->query("
                        SELECT 
                            e.cnpj_basico,
                            e.razao_social,
                            e.capital_social,
                            e.porte_empresa,
                            (
                                SELECT COUNT(*) 
                                FROM client_wallets cw 
                                WHERE cw.cnpj >= (e.cnpj_basico || '000000') AND cw.cnpj <= (e.cnpj_basico || '999999')
                            ) AS em_carteira_qtd
                        FROM receita.socios s
                        INNER JOIN receita.empresas e ON e.cnpj_basico = s.cnpj_basico
                        WHERE s.cpf_cnpj_socio = ?
                        LIMIT ? OFFSET ?
                    ", [$cleanQ, $limit + 1, $offset])->getResultArray();
                }
            } else {
                // Busca textual por Razão Social (apenas iniciando pelo termo, com paginação limite + 1)
                $searchType = 'name';
                $term = strtoupper($q) . '%';
                
                $results = $db->query("
                    SELECT 
                        e.cnpj_basico,
                        e.razao_social,
                        e.capital_social,
                        e.porte_empresa,
                        (
                            SELECT COUNT(*) 
                            FROM client_wallets cw 
                            WHERE cw.cnpj >= (e.cnpj_basico || '000000') AND cw.cnpj <= (e.cnpj_basico || '999999')
                        ) AS em_carteira_qtd
                    FROM receita.empresas e
                    WHERE e.razao_social LIKE ?
                    LIMIT ? OFFSET ?
                ", [$term, $limit + 1, $offset])->getResultArray();
            }
        }

        // Determina se há próxima/anterior página
        $hasNextPage = false;
        if (count($results) > $limit) {
            array_pop($results); // Remove o 51º elemento
            $hasNextPage = true;
        }
        $hasPrevPage = $page > 1;

        return view('admin/search/index', [
            'page_title'  => 'Consulta RFB (Receita Federal)',
            'q'           => $q,
            'results'     => $results,
            'searchType'  => $searchType,
            'page'        => $page,
            'hasNextPage' => $hasNextPage,
            'hasPrevPage' => $hasPrevPage
        ]);
    }

    /**
     * Exibe a ficha cadastral detalhada de uma empresa.
     */
    public function show(string $cnpjBasico): string
    {
        $db = db_connect();

        // 1. Busca os dados cadastrais básicos da empresa
        $empresa = $db->query("
            SELECT * 
            FROM receita.empresas 
            WHERE cnpj_basico = ?
        ", [$cnpjBasico])->getRowArray();

        if (!$empresa) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound("Empresa com CNPJ básico {$cnpjBasico} não encontrada.");
        }

        // 2. Busca os sócios cadastrados
        $socios = $db->query("
            SELECT * 
            FROM receita.socios 
            WHERE cnpj_basico = ?
            ORDER BY nome_socio ASC
        ", [$cnpjBasico])->getResultArray();

        // 3. Busca a situação atual desta empresa na carteira do SPIV
        $carteiras = $db->query("
            SELECT 
                cw.id,
                cw.cnpj,
                cw.status_operacional,
                cw.atribuido_em,
                cw.origem_atribuicao,
                v.nome AS vendor_nome,
                v.matricula AS vendor_matricula
            FROM client_wallets cw
            LEFT JOIN vendors v ON v.id = cw.vendor_id
            WHERE cw.cnpj >= ? AND cw.cnpj <= ?
            ORDER BY cw.cnpj ASC
        ", [$cnpjBasico . '000000', $cnpjBasico . '999999'])->getResultArray();

        // 4. Busca todos os estabelecimentos desta empresa
        $estabelecimentos = $db->query("
            SELECT e.*, m.descricao AS municipio_nome
            FROM receita.estabelecimentos e
            LEFT JOIN receita.municipios m ON e.municipio = m.codigo
            WHERE e.cnpj_basico = ?
            ORDER BY e.cnpj_ordem ASC
        ", [$cnpjBasico])->getResultArray();

        return view('admin/search/show', [
            'page_title'       => 'Detalhes Cadastrais RFB',
            'empresa'          => $empresa,
            'socios'           => $socios,
            'carteiras'        => $carteiras,
            'estabelecimentos' => $estabelecimentos
        ]);
    }
}
