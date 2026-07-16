<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use CodeIgniter\Shield\Entities\User;
use CodeIgniter\Shield\Models\UserModel;

class CreateDemoUsers extends BaseCommand
{
    protected $group       = 'Database';
    protected $name        = 'db:create-demo-users';
    protected $description = 'Cria no Shield os usuarios de teste da demo V0001-V0020 e C0001-C0003';

    public function run(array $params)
    {
        /** @var UserModel $userModel */
        $userModel = model(UserModel::class);
        $db = db_connect();

        // 1. Criar Vendedores V0001 a V0020
        for ($i = 1; $i <= 20; $i++) {
            $username = 'V' . str_pad((string)$i, 4, '0', STR_PAD_LEFT);
            $this->createUser($userModel, $db, $username, 'acom');
        }

        // 2. Criar Coordenadores C0001 a C0003
        for ($i = 1; $i <= 3; $i++) {
            $username = 'C' . str_pad((string)$i, 4, '0', STR_PAD_LEFT);
            $this->createUser($userModel, $db, $username, 'acom');
        }

        CLI::write("Todos os usuarios de teste foram criados com a senha '123'!", 'green');
    }

    private function createUser($userModel, $db, $username, $group)
    {
        $existing = $userModel->findByCredentials(['username' => $username]);
        if ($existing) {
            CLI::write("Usuario $username ja existe no Shield.", 'yellow');
            $userId = $existing->id;
        } else {
            $user = new User([
                'username' => $username,
                'email'    => $username . '@spiv.dev',
                'password' => '123',
                'active'   => 1,
            ]);

            $userModel->save($user);
            $userId = $userModel->getInsertID();

            $saved = $userModel->findById($userId);
            $saved->addGroup($group);

            CLI::write("Criado usuario no Shield: $username (grupo: $group)", 'green');
        }

        // Associa o ID do Shield na tabela vendor_users
        $db->table('vendor_users')
           ->where('matricula', $username)
           ->update(['shield_user_id' => $userId]);
    }
}
