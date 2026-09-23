<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

class EmployeeModel extends Model
{
    protected $table = 'employees';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = true;

    protected $allowedFields = [
        'employee_id', 'shield_user_id', 'display_name', 'organizational_unit',
        'identity_source', 'employment_status', 'last_synced_at',
    ];

    protected $validationRules = [
        'employee_id' => 'required|max_length[20]',
        'display_name' => 'required|max_length[200]',
        'identity_source' => 'required|in_list[demo,ldap]',
        'employment_status' => 'required|in_list[active,inactive,suspended,terminated]',
    ];

    public function findByEmployeeId(string $employeeId): ?array
    {
        return $this->where('employee_id', strtoupper(trim($employeeId)))->first();
    }

    public function findByShieldUserId(int $shieldUserId): ?array
    {
        return $this->where('shield_user_id', $shieldUserId)->first();
    }
}
