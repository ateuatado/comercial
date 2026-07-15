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
 *   89056580 → ACOM II       (grupo: acom, tipo_acom: II)
 *   89056581 → ACOM II       (grupo: acom, tipo_acom: II)
 *   89056582 → ACOM I        (grupo: acom, tipo_acom: I)
 *   89056583 → Gerente de Conta (grupo: gerente_conta)
 *   89056584 → Admin         (grupo: admin)
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

        $testUsers = [
            [
                'username'  => '89056580',
                'group'     => 'acom',
                'label'     => 'ACOM II (dev 1)',
                'tipo_acom' => 'II',
                'nome'      => 'Dev ACOM II-A',
                'estado_se' => 'SP',
            ],
            [
                'username'  => '89056581',
                'group'     => 'acom',
                'label'     => 'ACOM II (dev 2)',
                'tipo_acom' => 'II',
                'nome'      => 'Dev ACOM II-B',
                'estado_se' => 'SP',
            ],
            [
                'username'  => '89056582',
                'group'     => 'acom',
                'label'     => 'ACOM I (dev)',
                'tipo_acom' => 'I',
                'nome'      => 'Dev ACOM I',
                'estado_se' => 'SP',
            ],
            [
                'username'  => '89056583',
                'group'     => 'gerente_conta',
                'label'     => 'Gerente de Conta (dev)',
                'tipo_acom' => null,
                'nome'      => 'Dev Gerente de Conta',
                'estado_se' => 'SP',
            ],
            [
                'username'  => '89056584',
                'group'     => 'admin',
                'label'     => 'Admin (dev)',
                'tipo_acom' => null, // admin não é vendor
                'nome'      => null,
                'estado_se' => null,
            ],
        ];

        $db = db_connect();

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

                CLI::write("Criado usuário: {$data['username']} → {$data['label']}", 'green');
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
