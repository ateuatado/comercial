<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\EmployeeModel;
use App\Services\ApplicationAccessService;

class ApplicationsController extends BaseController
{
    public function index(): string
    {
        $user = auth()->user();
        $employee = (new EmployeeModel())->findByShieldUserId((int) $user->id);
        $applications = (new ApplicationAccessService())->applicationsFor((int) $user->id);

        return view('applications/index', compact('employee', 'applications'));
    }
}
