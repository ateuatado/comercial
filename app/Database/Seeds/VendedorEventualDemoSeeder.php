<?php

declare(strict_types=1);

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

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
            if ($this->db->table('employees')->where('employee_id', $employee['employee_id'])->countAllResults() > 0) {
                continue;
            }

            $this->db->table('employees')->insert($employee + [
                'identity_source' => 'demo',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
}
