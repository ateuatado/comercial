<?php

declare(strict_types=1);

namespace App\Controllers\VendedorEventual;

use App\Controllers\BaseController;
use App\Services\EnrollmentJourneyService;
use CodeIgniter\HTTP\RedirectResponse;
use DomainException;

class HomeController extends BaseController
{
    public function index(): string
    {
        $campaigns = (new EnrollmentJourneyService())->campaignsFor((int) auth()->user()->id);

        return view('vendedor_eventual/home', compact('campaigns'));
    }

    public function startEnrollment(int $campaignId): RedirectResponse
    {
        try {
            (new EnrollmentJourneyService())->start((int) auth()->user()->id, $campaignId);

            return redirect()->to('/vendedor-eventual')->with('success', 'Adesão voluntária iniciada.');
        } catch (DomainException $exception) {
            return redirect()->to('/vendedor-eventual')->with('error', $exception->getMessage());
        }
    }
}
