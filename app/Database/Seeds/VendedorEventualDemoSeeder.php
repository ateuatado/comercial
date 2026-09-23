<?php

declare(strict_types=1);

namespace App\Database\Seeds;

use App\Services\AccessAdministrationService;
use CodeIgniter\Database\Seeder;
use CodeIgniter\Shield\Entities\User;
use CodeIgniter\Shield\Models\UserModel;
use Config\VendedorEventual;

class VendedorEventualDemoSeeder extends Seeder
{
    public function run(): void
    {
        $now = date('Y-m-d H:i:s');
        $application = $this->db->table('ve_applications')->where('code', 'vendedor_eventual')->get()->getRowArray();
        if ($application === null) {
            $this->db->table('ve_applications')->insert([
                'code' => 'vendedor_eventual',
                'name' => 'Vendedor Eventual',
                'description' => 'Participação voluntária em campanhas comerciais demonstrativas.',
                'enabled' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $employees = [
            ['employee_id' => 'EMP0001', 'display_name' => 'Empregado Demonstração 1', 'organizational_unit' => 'Unidade Piloto', 'employment_status' => 'active'],
            ['employee_id' => 'EMP0002', 'display_name' => 'Empregado Demonstração 2', 'organizational_unit' => 'Unidade Piloto', 'employment_status' => 'active'],
            ['employee_id' => 'EMP0003', 'display_name' => 'Empregado Demonstração 3', 'organizational_unit' => 'Unidade Piloto', 'employment_status' => 'active'],
            ['employee_id' => 'GESTOR01', 'display_name' => 'Gestor Demonstração', 'organizational_unit' => 'Gestão do Piloto', 'employment_status' => 'active'],
            ['employee_id' => 'INATIVO01', 'display_name' => 'Empregado Inativo Demonstração', 'organizational_unit' => 'Unidade Piloto', 'employment_status' => 'inactive'],
        ];

        foreach ($employees as $employee) {
            $existing = $this->db->table('employees')
                ->where('employee_id', $employee['employee_id'])
                ->get()->getRowArray();

            if ($existing === null) {
                $this->db->table('employees')->insert($employee + [
                    'identity_source' => 'demo',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                $existing = $this->db->table('employees')
                    ->where('employee_id', $employee['employee_id'])
                    ->get()->getRowArray();
            }

            $this->provisionShieldUser($existing, $employee['employee_id'] === 'GESTOR01');
        }

        $this->seedReadyPilot();
    }

    private function provisionShieldUser(array $employee, bool $administrator): void
    {
        /** @var UserModel $userModel */
        $userModel = model(UserModel::class);
        $user = $userModel->findByCredentials(['username' => $employee['employee_id']]);

        if ($user === null) {
            $userModel->skipValidation(true)->save(new User([
                'username' => $employee['employee_id'],
                'email' => strtolower($employee['employee_id']) . '@demo.invalid',
                'active' => 1,
            ]));
            $user = $userModel->findById($userModel->getInsertID());
        }

        if ($user === null) {
            throw new \RuntimeException('Não foi possível provisionar o usuário demo ' . $employee['employee_id']);
        }

        if (empty($employee['shield_user_id'])) {
            $this->db->table('employees')->where('id', $employee['id'])->update([
                'shield_user_id' => (int) $user->id,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }

        if ($administrator && ! $user->inGroup('admin')) {
            $user->addGroup('admin');
        }
    }

    private function seedReadyPilot(): void
    {
        /** @var VendedorEventual $config */
        $config = config(VendedorEventual::class);
        if (! $config->enabled || ! $config->allowsDemoLogin()) {
            return;
        }

        $gestor = $this->db->table('employees')->where('employee_id', 'GESTOR01')->get()->getRowArray();
        $employee = $this->db->table('employees')->where('employee_id', 'EMP0001')->get()->getRowArray();
        $application = $this->db->table('ve_applications')->where('code', 'vendedor_eventual')->get()->getRowArray();
        if ($gestor === null || $employee === null || $application === null || empty($gestor['shield_user_id'])) {
            throw new \RuntimeException('Identidades ou aplicação do piloto não foram provisionadas.');
        }

        $administration = new AccessAdministrationService();
        $actorUserId = (int) $gestor['shield_user_id'];
        if (! $application['enabled']) {
            $administration->setApplicationEnabled((int) $application['id'], true, $actorUserId);
        }

        $campaign = $this->db->table('ve_campaigns')->where('code', 'PILOTO-DEMO')->get()->getRowArray();
        if ($campaign === null) {
            $campaignId = $administration->createCampaign([
                'code' => 'PILOTO-DEMO',
                'name' => 'Piloto Demonstrativo Local',
                'starts_at' => date('Y-m-d H:i:s', strtotime('-1 day')),
                'ends_at' => date('Y-m-d H:i:s', strtotime('+30 days')),
            ], $actorUserId);
            $administration->changeCampaignStatus($campaignId, 'published', $actorUserId);
            $administration->changeCampaignStatus($campaignId, 'active', $actorUserId);
            $campaign = $this->db->table('ve_campaigns')->where('id', $campaignId)->get()->getRowArray();
        }

        $alreadyGranted = $this->db->table('ve_employee_entitlements')
            ->where('employee_id', $employee['id'])
            ->where('application_id', $application['id'])
            ->where('campaign_id', $campaign['id'])
            ->where('status', 'active')
            ->countAllResults() > 0;

        if (! $alreadyGranted) {
            $administration->grant([
                'employee_id' => $employee['id'],
                'application_id' => $application['id'],
                'campaign_id' => $campaign['id'],
                'source' => 'campaign',
                'valid_from' => $campaign['starts_at'],
                'valid_until' => $campaign['ends_at'],
                'reason' => 'Configuração automática do piloto local fictício.',
            ], $actorUserId);
        }
    }
}
