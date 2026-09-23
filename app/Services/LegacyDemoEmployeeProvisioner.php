<?php

declare(strict_types=1);

namespace App\Services;

use CodeIgniter\Database\BaseConnection;

/**
 * Vincula as personas históricas da demonstração ao provedor demo fechado.
 */
class LegacyDemoEmployeeProvisioner
{
    public function __construct(private ?BaseConnection $db = null)
    {
        $this->db ??= db_connect();
    }

    /** @return list<string> */
    public static function employeeIds(): array
    {
        $ids = ['A0001', 'C0101', 'C0102', 'C0103'];

        for ($number = 101; $number <= 156; $number++) {
            $ids[] = 'V' . str_pad((string) $number, 4, '0', STR_PAD_LEFT);
        }

        return $ids;
    }

    /** @return array{created: int, updated: int, skipped: int, conflicts: int} */
    public function syncExistingShieldUsers(): array
    {
        $result = ['created' => 0, 'updated' => 0, 'skipped' => 0, 'conflicts' => 0];

        if (! $this->db->tableExists('users') || ! $this->db->tableExists('employees')) {
            $result['skipped'] = count(self::employeeIds());
            return $result;
        }

        foreach (self::employeeIds() as $employeeId) {
            $user = $this->db->table('users')
                ->select('id, username')
                ->where('username', $employeeId)
                ->get()->getRowArray();

            if ($user === null) {
                $result['skipped']++;
                continue;
            }

            $existing = $this->db->table('employees')
                ->where('employee_id', $employeeId)
                ->get()->getRowArray();
            $linkedElsewhere = $this->db->table('employees')
                ->where('shield_user_id', (int) $user['id'])
                ->where('employee_id !=', $employeeId)
                ->countAllResults() > 0;

            if ($linkedElsewhere || ($existing !== null && $existing['identity_source'] !== 'demo')) {
                $result['conflicts']++;
                continue;
            }

            $profile = $this->profile($employeeId);
            $now = date('Y-m-d H:i:s');
            $data = [
                'shield_user_id' => (int) $user['id'],
                'display_name' => $profile['display_name'],
                'organizational_unit' => $profile['organizational_unit'],
                'identity_source' => 'demo',
                'employment_status' => 'active',
                'last_synced_at' => $now,
                'updated_at' => $now,
            ];

            if ($existing === null) {
                $this->db->table('employees')->insert($data + [
                    'employee_id' => $employeeId,
                    'created_at' => $now,
                ]);
                $result['created']++;
                continue;
            }

            $this->db->table('employees')->where('id', $existing['id'])->update($data);
            $result['updated']++;
        }

        return $result;
    }

    /** @return array{display_name: string, organizational_unit: ?string} */
    private function profile(string $employeeId): array
    {
        if ($this->db->tableExists('vendor_users')) {
            $vendor = $this->db->table('vendor_users')
                ->select('nome, gerencia')
                ->where('matricula', $employeeId)
                ->get()->getRowArray();

            if ($vendor !== null) {
                return [
                    'display_name' => (string) $vendor['nome'],
                    'organizational_unit' => $vendor['gerencia'] ?: null,
                ];
            }
        }

        return [
            'display_name' => $employeeId === 'A0001' ? 'Administrador Fictício' : "Usuário Fictício {$employeeId}",
            'organizational_unit' => $employeeId === 'A0001' ? 'Administração da demonstração' : null,
        ];
    }
}
