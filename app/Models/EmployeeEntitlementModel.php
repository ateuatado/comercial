<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

class EmployeeEntitlementModel extends Model
{
    protected $table = 've_employee_entitlements';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'employee_id', 'application_id', 'campaign_id', 'capability', 'source',
        'status', 'valid_from', 'valid_until', 'granted_by', 'reason',
        'revoked_at', 'revoked_by',
    ];
}
