<?php

namespace App\Controllers;

use App\Models\VendorUserModel;
use CodeIgniter\HTTP\RedirectResponse;

/**
 * Controller: Home
 * Ponto de entrada após o login — redireciona conforme o perfil e vínculo do usuário.
 */
class Home extends BaseController
{
    /**
     * Rota padrão /
     *
     * Lógica de redirect:
     *   - Não autenticado         → /login
     *   - Admin                   → /admin/dashboard
     *   - Tem carteira (vendor_users) → /vendedor
     *   - Sem carteira            → /sem-carteira
     */
    public function index(): RedirectResponse
    {
        if (! auth()->loggedIn()) {
            return redirect()->to('/login');
        }

        $user = auth()->user();

        // Admin → painel administrativo
        if ($user->inGroup('admin')) {
            return redirect()->to('/admin/dashboard');
        }

        // Verifica se o usuário tem carteira em vendor_users
        $vendorModel = new VendorUserModel();
        $vendorUser  = $vendorModel->findByShieldUserId((int) $user->id);

        if ($vendorUser !== null && $vendorUser['ativo']) {
            // Tem carteira ativa → interface do vendedor
            return redirect()->to('/vendedor');
        }

        // Sem carteira → tela informativa
        return redirect()->to('/sem-carteira');
    }
}
