<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\ProspectingFlagModel;
use App\Models\ProspectingReviewModel;
use CodeIgniter\HTTP\RedirectResponse;

/**
 * Controller: Admin\ProspectingController
 *
 * Gerencia o fluxo de prospecção antifraude:
 *   - listagem e criação de suspeitas (admin)
 *   - revisão e decisão (admin com prospecting.approve ou supervisor)
 *
 * Segue o padrão controller fino: orquestração de request/response apenas.
 * Regras de negócio residem nos models ProspectingFlagModel e ProspectingReviewModel.
 */
class ProspectingController extends BaseController
{
    protected ProspectingFlagModel   $flagModel;
    protected ProspectingReviewModel $reviewModel;

    public function __construct()
    {
        $this->flagModel   = model(ProspectingFlagModel::class);
        $this->reviewModel = model(ProspectingReviewModel::class);
    }

    // ── Listagem ───────────────────────────────────────────────

    /** GET /admin/prospecting */
    public function index(): string
    {
        $se = $this->getAdminSE();

        return view('admin/prospecting/index', [
            'page_title' => 'Prospecção Antifraude',
            'flags'      => $this->flagModel->getAllWithEmpresa($se),
        ]);
    }

    // ── Criação ────────────────────────────────────────────────

    /** GET /admin/prospecting/nova */
    public function create(): string
    {
        return view('admin/prospecting/form', [
            'page_title' => 'Nova Suspeita',
            'validation' => \Config\Services::validation(),
        ]);
    }

    /** POST /admin/prospecting/nova */
    public function store(): RedirectResponse
    {
        $rules = [
            'cnpj'      => 'required|min_length[14]|max_length[14]',
            'cpf_socio' => 'required|min_length[11]|max_length[11]',
            'motivo'    => 'required|min_length[10]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $this->flagModel->insert([
            'cnpj'             => $this->request->getPost('cnpj'),
            'cpf_socio'        => $this->request->getPost('cpf_socio'),
            'cnpj_relacionado' => $this->request->getPost('cnpj_relacionado') ?: null,
            'motivo'           => $this->request->getPost('motivo'),
            'complemento'      => $this->request->getPost('complemento') ?: null,
            'analisado_por'    => (int) auth()->id(),
            'analisado_em'     => date('Y-m-d H:i:s'),
            'status'           => 'pendente',
        ]);

        return redirect()->to('/admin/prospecting')
                         ->with('success', 'Suspeita registrada com sucesso.');
    }

    // ── Detalhe ────────────────────────────────────────────────

    /** GET /admin/prospecting/(:num) */
    public function show(int $id): string|RedirectResponse
    {
        $data = $this->flagModel->getByIdWithReviews($id);

        if (! $data) {
            return redirect()->to('/admin/prospecting')
                             ->with('error', 'Suspeita não encontrada.');
        }

        return view('admin/prospecting/show', [
            'page_title' => 'Detalhe da Suspeita #' . $id,
            'flag'       => $data['flag'],
            'reviews'    => $data['reviews'],
        ]);
    }

    // ── Revisão ────────────────────────────────────────────────

    /** GET /admin/prospecting/(:num)/revisar */
    public function review(int $id): string|RedirectResponse
    {
        $data = $this->flagModel->getByIdWithReviews($id);

        if (! $data) {
            return redirect()->to('/admin/prospecting')
                             ->with('error', 'Suspeita não encontrada.');
        }

        if ($data['flag']['status'] !== 'pendente') {
            return redirect()->to('/admin/prospecting/' . $id)
                             ->with('error', 'Esta suspeita já foi revisada.');
        }

        return view('admin/prospecting/review', [
            'page_title' => 'Revisar Suspeita #' . $id,
            'flag'       => $data['flag'],
            'validation' => \Config\Services::validation(),
        ]);
    }

    /** POST /admin/prospecting/(:num)/revisar */
    public function decide(int $id): RedirectResponse
    {
        $flag = $this->flagModel->find($id);

        if (! $flag) {
            return redirect()->to('/admin/prospecting')
                             ->with('error', 'Suspeita não encontrada.');
        }

        if ($flag['status'] !== 'pendente') {
            return redirect()->to('/admin/prospecting/' . $id)
                             ->with('error', 'Esta suspeita já foi revisada.');
        }

        $rules = [
            'decisao'       => 'required|in_list[liberado,rejeitado]',
            'justificativa' => 'required|min_length[10]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $ok = $this->reviewModel->decide(
            flagId:       $id,
            revisadoPor:  (int) auth()->id(),
            decisao:      $this->request->getPost('decisao'),
            justificativa: $this->request->getPost('justificativa'),
        );

        if (! $ok) {
            return redirect()->back()->with('error', 'Erro ao salvar a decisão. Tente novamente.');
        }

        $label = $this->request->getPost('decisao') === 'liberado' ? 'Liberada' : 'Rejeitada';
        return redirect()->to('/admin/prospecting/' . $id)
                         ->with('success', "Suspeita {$label} com sucesso.");
    }
}
