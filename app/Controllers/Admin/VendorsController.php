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
        $post = $this->request->getPost(['matricula', 'nome', 'senha', 'lotacao', 'tipo_acom', 'estado_se']);

        if (! $this->validate($this->vendorModel->getValidationRules())) {
            return view('admin/vendors/form', [
                'page_title' => 'Novo Vendedor',
                'vendor'     => null,
                'action_url' => '/admin/vendors/novo',
                'errors'     => $this->validator->getErrors(),
                'old'        => $post,
            ]);
        }

        $matricula = strtoupper(trim($post['matricula']));
        $nome      = trim($post['nome']);
        $senha     = trim($post['senha'] ?? '');
        $senha     = !empty($senha) ? $senha : '123';

        if ($this->vendorModel->isMatriculaTaken($matricula)) {
            return view('admin/vendors/form', [
                'page_title' => 'Novo Vendedor',
                'vendor'     => null,
                'action_url' => '/admin/vendors/novo',
                'errors'     => ['matricula' => 'Matrícula já cadastrada.'],
                'old'        => $post,
            ]);
        }

        // 1. Criar ou atualizar Shield User (Login)
        $userModel = model(\CodeIgniter\Shield\Models\UserModel::class);
        $user      = $userModel->findByCredentials(['username' => $matricula]);

        if ($user === null) {
            $newUser = new \CodeIgniter\Shield\Entities\User([
                'username' => $matricula,
                'email'    => $matricula . '@correios.com.br',
                'password' => $senha,
                'active'   => 1,
            ]);
            $userModel->skipValidation(true)->save($newUser);
            $user = $userModel->findById($userModel->getInsertID());
            $user->addGroup('acom');
        } else {
            $user->password = $senha;
            $userModel->skipValidation(true)->save($user);
        }

        // 2. Criar ou atualizar vendor_users
        $vendorUserModel = new \App\Models\VendorUserModel();
        $existingVendorUser = $vendorUserModel->findByMatricula($matricula);
        $tipoAcom = $post['tipo_acom'] ?: 'GC';

        if ($existingVendorUser === null) {
            $vendorUserModel->insert([
                'matricula'       => $matricula,
                'nome'            => $nome,
                'perfil_vendedor' => $tipoAcom === 'GC' ? 'GC' : 'ACOM ' . $tipoAcom,
                'se'              => $post['estado_se'] ?: 'SP',
                'shield_user_id'  => $user->id,
                'ativo'           => true,
            ]);
        } else {
            $vendorUserModel->update($existingVendorUser['id'], [
                'nome'            => $nome,
                'perfil_vendedor' => $tipoAcom === 'GC' ? 'GC' : 'ACOM ' . $tipoAcom,
                'se'              => $post['estado_se'] ?: $existingVendorUser['se'],
                'shield_user_id'  => $user->id,
                'ativo'           => true,
            ]);
        }

        // 3. Inserir em vendors (legado)
        $this->vendorModel->insert([
            'matricula' => $matricula,
            'nome'      => $nome,
            'user_id'   => $user->id,
            'lotacao'   => $post['lotacao'] ?: null,
            'tipo_acom' => $post['tipo_acom'] ?: null,
            'estado_se' => $post['estado_se'] ?: null,
            'ativo'     => true,
        ]);

        return redirect()->to('/admin/vendors')
            ->with('success', "Vendedor '{$nome}' (Matrícula: {$matricula}) cadastrado com sucesso!");
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

        $post = $this->request->getPost(['matricula', 'nome', 'senha', 'lotacao', 'tipo_acom', 'estado_se']);

        if (! $this->validate($this->vendorModel->getValidationRules())) {
            return view('admin/vendors/form', [
                'page_title' => 'Editar Vendedor',
                'vendor'     => $vendor,
                'action_url' => "/admin/vendors/{$id}/editar",
                'errors'     => $this->validator->getErrors(),
                'old'        => $post,
            ]);
        }

        $matricula = strtoupper(trim($post['matricula']));
        $nome      = trim($post['nome']);
        $senha     = trim($post['senha'] ?? '');

        if ($this->vendorModel->isMatriculaTaken($matricula, $id)) {
            return view('admin/vendors/form', [
                'page_title' => 'Editar Vendedor',
                'vendor'     => $vendor,
                'action_url' => "/admin/vendors/{$id}/editar",
                'errors'     => ['matricula' => 'Matrícula já cadastrada para outro vendedor.'],
                'old'        => $post,
            ]);
        }

        // Atualizar Shield User se senha informada
        $userModel = model(\CodeIgniter\Shield\Models\UserModel::class);
        $user      = $userModel->findByCredentials(['username' => $matricula]);
        if ($user !== null && !empty($senha)) {
            $user->password = $senha;
            $userModel->skipValidation(true)->save($user);
        }

        // Atualizar vendor_users
        $vendorUserModel = new \App\Models\VendorUserModel();
        $existingVendorUser = $vendorUserModel->findByMatricula($matricula);
        $tipoAcom = $post['tipo_acom'] ?: 'GC';

        if ($existingVendorUser !== null) {
            $vendorUserModel->update($existingVendorUser['id'], [
                'nome'            => $nome,
                'perfil_vendedor' => $tipoAcom === 'GC' ? 'GC' : 'ACOM ' . $tipoAcom,
                'se'              => $post['estado_se'] ?: $existingVendorUser['se'],
            ]);
        }

        $this->vendorModel->update($id, [
            'matricula' => $matricula,
            'nome'      => $nome,
            'lotacao'   => $post['lotacao'] ?: null,
            'tipo_acom' => $post['tipo_acom'] ?: null,
            'estado_se' => $post['estado_se'] ?: null,
        ]);

        return redirect()->to('/admin/vendors')
            ->with('success', "Vendedor '{$nome}' atualizado com sucesso.");
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
