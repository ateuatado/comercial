<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

class VendedorEventualApplicationModel extends Model
{
    protected $table = 've_applications';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = ['code', 'name', 'description', 'enabled'];
}
