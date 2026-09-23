<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

class VendedorEventualCampaignModel extends Model
{
    protected $table = 've_campaigns';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = ['code', 'name', 'mode', 'status', 'starts_at', 'ends_at', 'created_by'];
}
