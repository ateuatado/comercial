<?php

declare(strict_types=1);

namespace App\Controllers\Auth;

use App\Libraries\LdapService;
use App\Models\EmployeeModel;
use App\Models\VendorUserModel;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\Shield\Controllers\LoginController as ShieldLoginController;
use CodeIgniter\Shield\Entities\User;
use CodeIgniter\Shield\Models\UserModel;
use Config\VendedorEventual;

/**
 * LoginController — Autenticação do SPIV.
 *
 * Comportamento controlado por variável de ambiente:
 *
 *   LDAP_ENABLED = false  →  autenticação local via Shield (desenvolvimento).
 *   LDAP_ENABLED = true   →  autenticação via LDAP dos Correios (produção).
 *
 * Auto-provisioning:
 *   - No primeiro login, o sistema cria automaticamente o usuário Shield.
 *   - Se a matrícula existir em vendor_users, vincula o shield_user_id.
 *   - Se não existir, o usuário será redirecionado para /sem-carteira.
 *
 * No piloto externo, o provedor demo aceita somente empregados fictícios
 * cadastrados e apenas quando habilitado explicitamente por configuração.
 */
class LoginController extends ShieldLoginController
{
    /**
     * Tenta autenticar o usuário.
     * Delega para LDAP ou para a autenticação local do Shield conforme o ambiente.
     */
    public function loginAction(): RedirectResponse
    {
        $matricula = strtoupper(trim((string) $this->request->getPost('username')));
        $password  = (string) $this->request->getPost('password');

        if (empty($matricula) || empty($password)) {
            return redirect()->route('login')->withInput()->with('error', 'Matrícula e senha são obrigatórias.');
        }

        /** @var VendedorEventual $veConfig */
        $veConfig = config(VendedorEventual::class);

        if ($veConfig->identityProvider === 'demo') {
            return $this->demoLoginAction($matricula, $password, $veConfig);
        }

        // Autenticação local via Shield (Matrícula + Senha personalizada)
        $result = auth('session')->attempt([
            'username' => $matricula,
            'password' => $password,
        ]);

        if ($result->isOK()) {
            $user = auth()->user();
            $vendorModel = new VendorUserModel();
            $vendorUser  = $vendorModel->findByMatricula($matricula);

            if ($vendorUser !== null) {
                if (empty($vendorUser['shield_user_id'])) {
                    $vendorModel->linkShieldUser((int) $vendorUser['id'], (int) $user->id);
                }
                $this->syncVendorGroup($user, $vendorUser);
            } else {
                if (!$user->inGroup('admin') && !$user->inGroup('acom') && !$user->inGroup('gerente_conta')) {
                    $user->addGroup('acom');
                }
            }

            return redirect()->to('/')->withCookies();
        }

        if (filter_var(env('LDAP_ENABLED', 'false'), FILTER_VALIDATE_BOOLEAN)) {
            return $this->ldapLoginAction();
        }

        return redirect()->route('login')->withInput()->with('error', 'Matrícula ou senha inválidos.');
    }

    /**
     * Auto-provisioning: cria ou encontra usuário Shield, vincula com vendor_users.
     *
     * Fluxo:
     *  1. Busca/cria Shield user para a matrícula.
     *  2. Busca vendor_users por matrícula.
     *  3. Se encontrar e shield_user_id for null → vincula.
     *  4. Define grupo com base em ter ou não carteira + ser coordenador.
     *  5. Loga e redireciona.
     */
    private function autoProvisionAndLogin(string $matricula): RedirectResponse
    {
        /** @var UserModel $userModel */
        $userModel   = model(UserModel::class);
        $vendorModel = new VendorUserModel();
        $employeeModel = new EmployeeModel();

        // 1. Encontra ou cria o Shield user
        $user = $userModel->findByCredentials(['username' => $matricula]);

        if ($user === null) {
            $newUser = new User([
                'username' => $matricula,
                'email'    => $matricula . '@correios.com.br',
                'active'   => 1,
            ]);
            $userModel->skipValidation(true)->save($newUser);
            $user = $userModel->findById($userModel->getInsertID());

            log_message('info', "[LoginController] Shield user criado via auto-provisioning: {$matricula}");
        }

        // 2. Busca vendor_users por matrícula
        $vendorUser = $vendorModel->findByMatricula($matricula);
        $employee = $employeeModel->findByEmployeeId($matricula);

        if ($employee !== null && empty($employee['shield_user_id'])) {
            $employeeModel->update((int) $employee['id'], [
                'shield_user_id' => (int) $user->id,
                'last_synced_at' => date('Y-m-d H:i:s'),
            ]);
        }

        if ($vendorUser !== null) {
            // 3. Vincula shield_user_id se ainda não estiver vinculado
            if (empty($vendorUser['shield_user_id'])) {
                $vendorModel->linkShieldUser((int) $vendorUser['id'], (int) $user->id);
                log_message('info', "[LoginController] Vendedor vinculado: matrícula={$matricula}, shield_id={$user->id}");
            }

            // 4. Garante que o usuário tem o grupo correto
            if (!$user->inGroup('admin')) {
                // Remove grupos anteriores e define baseado no perfil
                $this->syncVendorGroup($user, $vendorUser);
            }

            // Vincula também na tabela vendors legada (compatibilidade)
            $db = \Config\Database::connect();
            $db->table('vendors')
               ->where('matricula', $matricula)
               ->where('user_id IS NULL')
               ->update(['user_id' => $user->id]);
        } elseif ($employee === null) {
            // Sem carteira — garante grupo básico
            if (!$user->inGroup('admin') && !$user->inGroup('acom') && !$user->inGroup('gerente_conta')) {
                $user->addGroup('acom');
            }

            log_message('info', "[LoginController] Matrícula sem carteira: {$matricula} → /sem-carteira");
        }

        // 5. Loga e redireciona
        auth('session')->login($user);

        return redirect()->to('/')->withCookies();
    }

    private function demoLoginAction(string $matricula, string $password, VendedorEventual $config): RedirectResponse
    {
        if (! $config->allowsDemoLogin() || ! hash_equals($config->demoPassword, $password)) {
            return redirect()->route('login')->withInput()->with('error', 'Matrícula ou senha inválidos.');
        }

        $employee = (new EmployeeModel())->findByEmployeeId($matricula);
        if ($employee === null
            || $employee['identity_source'] !== 'demo'
            || $employee['employment_status'] !== 'active') {
            return redirect()->route('login')->withInput()->with('error', 'Matrícula ou senha inválidos.');
        }

        return $this->autoProvisionAndLogin($matricula);
    }

    /**
     * Sincroniza o grupo Shield com base no perfil do vendor_user.
     */
    private function syncVendorGroup($user, array $vendorUser): void
    {
        $perfil = $vendorUser['perfil_vendedor'] ?? '';

        // Remove grupos antigos se necessário
        foreach (['acom', 'gerente_conta'] as $group) {
            if ($user->inGroup($group)) {
                $user->removeGroup($group);
            }
        }

        // Gerente de Conta
        if (strtoupper($perfil) === 'GC') {
            $user->addGroup('gerente_conta');
        } else {
            $user->addGroup('acom');
        }
    }

    /**
     * Sobrescreve as regras de validação do Shield.
     * O padrão do Shield valida 'email', mas o SPIV usa matrícula (username).
     */
    protected function getValidationRules(): array
    {
        return [
            'username' => [
                'label'  => 'Matrícula',
                'rules'  => ['required', 'max_length[20]', 'min_length[3]'],
                'errors' => [
                    'required'   => 'A matrícula é obrigatória.',
                    'max_length' => 'Matrícula inválida.',
                    'min_length' => 'Matrícula inválida.',
                ],
            ],
            'password' => [
                'label'  => 'Senha',
                'rules'  => ['required'],
                'errors' => [
                    'required' => 'A senha é obrigatória.',
                ],
            ],
        ];
    }

    /**
     * Fluxo de autenticação via LDAP (rede dos Correios).
     *
     * 1. Valida matrícula + senha contra o LDAP.
     * 2. Se válido: usa autoProvisionAndLogin para encontrar/criar/vincular.
     */
    private function ldapLoginAction(): RedirectResponse
    {
        $matricula = strtoupper(trim((string) $this->request->getPost('username')));
        $password  = $this->request->getPost('password');

        if (empty($matricula) || empty($password)) {
            return redirect()->route('login')
                ->withInput()
                ->with('error', 'Matrícula e senha são obrigatórias.');
        }

        $ldap = new LdapService();

        if (! $ldap->authenticate($matricula, $password)) {
            return redirect()->route('login')
                ->withInput()
                ->with('error', 'Matrícula ou senha inválidos.');
        }

        // Credenciais LDAP válidas — usa o mesmo fluxo de auto-provisioning.
        return $this->autoProvisionAndLogin($matricula);
    }

    /**
     * Faz logout do usuário e redireciona para a página de login.
     */
    public function logoutAction(): RedirectResponse
    {
        auth('session')->logout();

        return redirect()->to('login')
            ->with('message', 'Você foi desconectado com sucesso.');
    }
}
