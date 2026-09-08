<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use App\Services\LegacyDemoEmployeeProvisioner;
use CodeIgniter\Database\Migration;

class RegisterLegacyDemoEmployees extends Migration
{
    public function up(): void
    {
        $result = (new LegacyDemoEmployeeProvisioner($this->db))->syncExistingShieldUsers();
        log_message('info', 'Sincronização das identidades demo legadas: {result}', [
            'result' => json_encode($result, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
        ]);
    }

    public function down(): void
    {
        if (! $this->db->tableExists('employees')) {
            return;
        }

        $this->db->table('employees')
            ->where('identity_source', 'demo')
            ->whereIn('employee_id', LegacyDemoEmployeeProvisioner::employeeIds())
            ->delete();
    }
}
