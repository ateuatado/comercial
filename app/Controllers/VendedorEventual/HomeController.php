<?php

declare(strict_types=1);

namespace App\Controllers\VendedorEventual;

use App\Controllers\BaseController;
use App\Services\EnrollmentJourneyService;
use App\Services\LearningJourneyService;
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

    public function training(int $campaignId): string|RedirectResponse
    {
        try {
            $learning = (new LearningJourneyService())->publishedFor((int) auth()->user()->id, $campaignId);

            return view('vendedor_eventual/training', compact('learning', 'campaignId'));
        } catch (DomainException $exception) {
            return redirect()->to('/vendedor-eventual')->with('error', $exception->getMessage());
        }
    }

    public function completeTraining(int $campaignId): RedirectResponse
    {
        try {
            (new LearningJourneyService())->complete(
                (int) auth()->user()->id,
                $campaignId,
                (int) $this->request->getPost('answer'),
                $this->request->getPost('accepted_terms') === '1'
            );

            return redirect()->to('/vendedor-eventual')->with('success', 'Capacitação concluída e participação habilitada.');
        } catch (DomainException $exception) {
            return redirect()->back()->withInput()->with('error', $exception->getMessage());
        }
    }
}
