<?php

declare(strict_types=1);

namespace App\Database\Seeds;

use CodeIgniter\CLI\CLI;
use CodeIgniter\Database\Seeder;
use CodeIgniter\Shield\Entities\User;
use CodeIgniter\Shield\Models\UserModel;

/**
 * Seeder de desenvolvimento — NÃO usar em produção.
 *
 * Cria usuários de teste com matrícula e senha hardcoded para uso enquanto
 * a integração LDAP não estiver disponível fora da rede interna dos Correios.
 *
 * Usuários criados (senha: 123):
 *   V0021 → ACOM II       (grupo: acom, tipo_acom: II)
 *   V0022 → ACOM II       (grupo: acom, tipo_acom: II)
 *   V0023 → ACOM I        (grupo: acom, tipo_acom: I)
 *   V0024 → Gerente de Conta (grupo: gerente_conta)
 *   A0001 → Admin         (grupo: admin)
 *
 * Executar com:
 *   php spark db:seed DevUsersSeeder
 *
 * O seeder é idempotente: pula matrículas já existentes.
 * Também cria registros em vendors para os perfis operacionais.
 */
class DevUsersSeeder extends Seeder
{
    public function run(): void
    {
        /** @var UserModel $userModel */
        $userModel = model(UserModel::class);
        $db = db_connect();

        // 1. Limpar usuários antigos com matrícula real/realista para não expor dados
        $oldUsernames = ['89056580', '89056581', '89056582', '89056583', '89056584'];
        foreach ($oldUsernames as $username) {
            $existing = $userModel->findByCredentials(['username' => $username]);
            if ($existing) {
                CLI::write("Removendo usuário antigo com matrícula realista: {$username}", 'yellow');
                $userModel->delete($existing->id, true);
            }
        }
        $db->table('vendors')->whereIn('matricula', $oldUsernames)->delete();
        $db->table('vendor_users')->whereIn('matricula', $oldUsernames)->delete();

        // 2. Definir novos usuários fictícios seguindo o padrão seguro (A0001 para Admin, V0021-V0024 para Vendors)
        $testUsers = [
            [
                'username'  => 'V0021',
                'group'     => 'acom',
                'label'     => 'ACOM II (dev 1)',
                'tipo_acom' => 'II',
                'nome'      => 'ACOM Fictício II-A',
                'estado_se' => 'SP',
            ],
            [
                'username'  => 'V0022',
                'group'     => 'acom',
                'label'     => 'ACOM II (dev 2)',
                'tipo_acom' => 'II',
                'nome'      => 'ACOM Fictício II-B',
                'estado_se' => 'SP',
            ],
            [
                'username'  => 'V0023',
                'group'     => 'acom',
                'label'     => 'ACOM I (dev)',
                'tipo_acom' => 'I',
                'nome'      => 'ACOM Fictício I',
                'estado_se' => 'SP',
            ],
            [
                'username'  => 'V0024',
                'group'     => 'gerente_conta',
                'label'     => 'Gerente de Conta (dev)',
                'tipo_acom' => null,
                'nome'      => 'Gerente de Conta Fictício',
                'estado_se' => 'SP',
            ],
            [
                'username'  => 'A0001',
                'group'     => 'admin',
                'label'     => 'Admin (dev)',
                'tipo_acom' => null, // admin não é vendor
                'nome'      => null,
                'estado_se' => null,
            ],
        ];

        foreach ($testUsers as $data) {
            $existingUser = $userModel->findByCredentials(['username' => $data['username']]);

            if ($existingUser) {
                CLI::write("Usuário {$data['username']} já existe — verificando vendor record...", 'yellow');
                $userId = $existingUser->id;
            } else {
                // Cria usuário no Shield.
                $user = new User([
                    'username' => $data['username'],
                    'email'    => $data['username'] . '@spiv.dev',
                    'password' => '123',
                    'active'   => 1,
                ]);

                $userModel->save($user);
                $userId = $userModel->getInsertID();

                $saved = $userModel->findById($userId);
                $saved->addGroup($data['group']);

                CLI::write("Criado usuário fictício: {$data['username']} → {$data['label']}", 'green');
            }

            // Cria registro em vendors para perfis operacionais (não admin).
            if ($data['group'] !== 'admin' && $data['nome'] !== null) {
                $exists = $db->table('vendors')
                             ->where('matricula', $data['username'])
                             ->countAllResults();

                if (! $exists) {
                    $db->table('vendors')->insert([
                        'user_id'    => $userId,
                        'matricula'  => $data['username'],
                        'nome'       => $data['nome'],
                        'tipo_acom'  => $data['tipo_acom'],
                        'estado_se'  => $data['estado_se'],
                        'ativo'      => true,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);
                    CLI::write("  → Vendor criado: {$data['nome']} (tipo_acom: " . ($data['tipo_acom'] ?? 'GC') . ')', 'cyan');
                } else {
                    CLI::write("  → Vendor já existe: {$data['username']}", 'yellow');
                }
            }
        }

        CLI::write('DevUsersSeeder concluído.', 'green');
    }
}
