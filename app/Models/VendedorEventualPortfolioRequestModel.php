<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

class VendedorEventualPortfolioRequestModel extends Model
{
    protected $table = 've_portfolio_requests';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'request_id', 'opportunity_id', 'employee_id', 'cnpj', 'reservation_id',
        'status', 'requested_at',
    ];
}
