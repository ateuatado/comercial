<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\VendorModel;
use CodeIgniter\HTTP\RedirectResponse;

/**
 * Controller: Admin\VendorsController
 * CRUD de vendedores (ACOMs e Gerentes de Conta) para o perfil admin.
 */
class VendorsController extends BaseController
{
    private VendorModel $vendorModel;

    public function __construct()
    {
        $this->vendorModel = new VendorModel();
    }

    /** GET /admin/vendors — lista todos os vendedores. */
    public function index(): string
    {
        $se = $this->getAdminSE();

        $vendors = $this->vendorModel
            ->when($se, fn($query) => $query->where('estado_se', $se))
            ->orderBy('ativo', 'DESC')
            ->orderBy('nome', 'ASC')
            ->findAll();

        return view('admin/vendors/index', [
            'page_title' => 'Vendedores',
            'vendors'    => $vendors,
        ]);
    }

    /** GET /admin/vendors/novo — exibe formulário de cadastro. */
    public function create(): string
    {
        return view('admin/vendors/form', [
            'page_title' => 'Novo Vendedor',
            'vendor'     => null,
            'action_url' => '/admin/vendors/novo',
        ]);
    }

    /** POST /admin/vendors/novo — processa cadastro. */
    public function store(): string|RedirectResponse
    {
        $post = $this->request->getPost(['matricula', 'nome', 'lotacao', 'tipo_acom', 'estado_se']);

        if (! $this->validate($this->vendorModel->getValidationRules())) {
            return view('admin/vendors/form', [
                'page_title' => 'Novo Vendedor',
                'vendor'     => null,
                'action_url' => '/admin/vendors/novo',
                'errors'     => $this->validator->getErrors(),
                'old'        => $post,
            ]);
        }

        if ($this->vendorModel->isMatriculaTaken($post['matricula'])) {
            return view('admin/vendors/form', [
                'page_title' => 'Novo Vendedor',
                'vendor'     => null,
                'action_url' => '/admin/vendors/novo',
                'errors'     => ['matricula' => 'Matrícula já cadastrada.'],
                'old'        => $post,
            ]);
        }

        $this->vendorModel->insert([
            'matricula' => $post['matricula'],
            'nome'      => $post['nome'],
            'lotacao'   => $post['lotacao'] ?: null,
            'tipo_acom' => $post['tipo_acom'] ?: null,
            'estado_se' => $post['estado_se'] ?: null,
            'ativo'     => true,
        ]);

        return redirect()->to('/admin/vendors')
            ->with('success', 'Vendedor cadastrado com sucesso.');
    }

    /** GET /admin/vendors/(:num)/editar — exibe formulário de edição. */
    public function edit(int $id): string|RedirectResponse
    {
        $vendor = $this->vendorModel->find($id);
        if (! $vendor) {
            return redirect()->to('/admin/vendors')
                ->with('error', 'Vendedor não encontrado.');
        }

        return view('admin/vendors/form', [
            'page_title' => 'Editar Vendedor',
            'vendor'     => $vendor,
            'action_url' => "/admin/vendors/{$id}/editar",
        ]);
    }

    /** POST /admin/vendors/(:num)/editar — processa edição. */
    public function update(int $id): string|RedirectResponse
    {
        $vendor = $this->vendorModel->find($id);
        if (! $vendor) {
            return redirect()->to('/admin/vendors')
                ->with('error', 'Vendedor não encontrado.');
        }

        $post = $this->request->getPost(['matricula', 'nome', 'lotacao', 'tipo_acom', 'estado_se']);

        if (! $this->validate($this->vendorModel->getValidationRules())) {
            return view('admin/vendors/form', [
                'page_title' => 'Editar Vendedor',
                'vendor'     => $vendor,
                'action_url' => "/admin/vendors/{$id}/editar",
                'errors'     => $this->validator->getErrors(),
                'old'        => $post,
            ]);
        }

        if ($this->vendorModel->isMatriculaTaken($post['matricula'], $id)) {
            return view('admin/vendors/form', [
                'page_title' => 'Editar Vendedor',
                'vendor'     => $vendor,
                'action_url' => "/admin/vendors/{$id}/editar",
                'errors'     => ['matricula' => 'Matrícula já cadastrada para outro vendedor.'],
                'old'        => $post,
            ]);
        }

        $this->vendorModel->update($id, [
            'matricula' => $post['matricula'],
            'nome'      => $post['nome'],
            'lotacao'   => $post['lotacao'] ?: null,
            'tipo_acom' => $post['tipo_acom'] ?: null,
            'estado_se' => $post['estado_se'] ?: null,
        ]);

        return redirect()->to('/admin/vendors')
            ->with('success', 'Vendedor atualizado com sucesso.');
    }

    /** POST /admin/vendors/(:num)/desativar — desativa (soft-delete). */
    public function deactivate(int $id): RedirectResponse
    {
        $vendor = $this->vendorModel->find($id);
        if (! $vendor) {
            return redirect()->to('/admin/vendors')
                ->with('error', 'Vendedor não encontrado.');
        }

        if (! $vendor['ativo']) {
            return redirect()->to('/admin/vendors')
                ->with('info', 'Vendedor já está inativo.');
        }

        $this->vendorModel->deactivate($id);

        return redirect()->to('/admin/vendors')
            ->with('success', "Vendedor {$vendor['nome']} desativado. Histórico preservado.");
    }
}
