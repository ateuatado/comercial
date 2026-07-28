<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\RedirectResponse;

/**
 * Controller: Admin\EventosController
 * Gestão de Eventos / Feiras e acompanhamento de contatos captados.
 */
class EventosController extends BaseController
{
    /** GET /admin/eventos — lista todos os eventos cadastrados. */
    public function index(): string
    {
        $db = db_connect();

        $eventos = $db->query("
            SELECT e.*,
                   COUNT(ec.id) AS total_contatos
            FROM eventos e
            LEFT JOIN evento_contacts ec ON ec.evento_id = e.id
            GROUP BY e.id
            ORDER BY e.created_at DESC
        ")->getResultArray();

        return view('admin/eventos_index', [
            'page_title' => 'Eventos & Feiras',
            'eventos'    => $eventos,
        ]);
    }

    /** POST /admin/eventos/novo — cria um novo evento. */
    public function store(): RedirectResponse
    {
        $nome       = trim($this->request->getPost('nome') ?? '');
        $local      = trim($this->request->getPost('local') ?? '');
        $dataInicio = $this->request->getPost('data_inicio') ?: null;
        $dataFim    = $this->request->getPost('data_fim') ?: null;

        if (empty($nome)) {
            return redirect()->back()->with('error', 'O nome do evento é obrigatório.')->withInput();
        }

        $user = auth()->user();
        $createdBy = $user ? $user->username : 'admin';

        $db = db_connect();
        $db->table('eventos')->insert([
            'nome'        => $nome,
            'local'       => $local ?: null,
            'data_inicio' => $dataInicio,
            'data_fim'    => $dataFim,
            'ativo'       => true,
            'created_by'  => $createdBy,
            'created_at'  => date('Y-m-d H:i:s'),
            'updated_at'  => date('Y-m-d H:i:s'),
        ]);

        return redirect()->to(site_url('admin/eventos'))
            ->with('success', "Evento '{$nome}' cadastrado com sucesso!");
    }

    /** POST /admin/eventos/(:num)/toggle — altera o status (ativo/inativo). */
    public function toggle(int $id): RedirectResponse
    {
        $db = db_connect();
        $evento = $db->table('eventos')->where('id', $id)->get()->getRowArray();

        if (!$evento) {
            return redirect()->to(site_url('admin/eventos'))->with('error', 'Evento não encontrado.');
        }

        $novoStatus = !$evento['ativo'];
        $db->table('eventos')->where('id', $id)->update([
            'ativo'      => $novoStatus,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $msg = $novoStatus ? "Evento '{$evento['nome']}' ativado." : "Evento '{$evento['nome']}' desativado.";
        return redirect()->to(site_url('admin/eventos'))->with('success', $msg);
    }

    /** GET /admin/eventos/(:num) — detalhe do evento e lista de contatos captados. */
    public function show(int $id): string|RedirectResponse
    {
        $db = db_connect();
        $evento = $db->table('eventos')->where('id', $id)->get()->getRowArray();

        if (!$evento) {
            return redirect()->to(site_url('admin/eventos'))->with('error', 'Evento não encontrado.');
        }

        $filtroStatus  = trim($this->request->getGet('status') ?? '');
        $filtroVendedor= trim($this->request->getGet('vendedor') ?? '');

        $sql = "
            SELECT ec.*,
                   COALESCE(vu.nome, ec.matricula_vendedor) AS nome_vendedor
            FROM evento_contacts ec
            LEFT JOIN vendor_users vu ON vu.matricula = ec.matricula_vendedor
            WHERE ec.evento_id = ?
        ";
        $params = [$id];

        if (!empty($filtroStatus)) {
            $sql .= " AND ec.status = ?";
            $params[] = $filtroStatus;
        }

        if (!empty($filtroVendedor)) {
            $sql .= " AND ec.matricula_vendedor = ?";
            $params[] = $filtroVendedor;
        }

        $sql .= " ORDER BY ec.created_at DESC";

        $contatos = $db->query($sql, $params)->getResultArray();

        // Totais por status
        $totaisRows = $db->query("
            SELECT status, COUNT(*) AS total
            FROM evento_contacts
            WHERE evento_id = ?
            GROUP BY status
        ", [$id])->getResultArray();

        $totais = array_column($totaisRows, 'total', 'status');

        // Lista de vendedores participantes
        $vendedores = $db->query("
            SELECT DISTINCT ec.matricula_vendedor, COALESCE(vu.nome, ec.matricula_vendedor) AS nome_vendedor
            FROM evento_contacts ec
            LEFT JOIN vendor_users vu ON vu.matricula = ec.matricula_vendedor
            WHERE ec.evento_id = ?
            ORDER BY nome_vendedor ASC
        ", [$id])->getResultArray();

        return view('admin/evento_detalhe', [
            'page_title'     => 'Detalhes do Evento: ' . $evento['nome'],
            'evento'         => $evento,
            'contatos'       => $contatos,
            'totais'         => $totais,
            'vendedores'     => $vendedores,
            'filtroStatus'   => $filtroStatus,
            'filtroVendedor' => $filtroVendedor,
        ]);
    }
}
