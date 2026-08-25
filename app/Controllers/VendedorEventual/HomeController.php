<?php

declare(strict_types=1);

namespace App\Controllers\VendedorEventual;

use App\Controllers\BaseController;

class HomeController extends BaseController
{
    public function index(): string
    {
        return view('vendedor_eventual/home');
    }
}
