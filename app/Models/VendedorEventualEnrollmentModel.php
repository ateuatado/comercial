<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

class VendedorEventualEnrollmentModel extends Model
{
    protected $table = 've_enrollments';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'campaign_id', 'employee_id', 'status', 'terms_version', 'terms_accepted_at',
        'training_version', 'training_completed_at', 'assessment_score',
        'assessment_passed', 'qualified_until', 'enrolled_at', 'status_reason',
    ];
}
