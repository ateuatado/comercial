<?php

declare(strict_types=1);

namespace App\Controllers\VendedorEventual;

use App\Controllers\BaseController;
use App\Models\EmployeeModel;
use App\Services\EnrollmentJourneyService;
use App\Services\EnrollmentService;
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

    public function changeEnrollmentStatus(int $campaignId, string $action): RedirectResponse
    {
        try {
            $userId = (int) auth()->user()->id;
            $employee = (new EmployeeModel())->findByShieldUserId($userId);
            $enrollment = $employee === null ? null : db_connect()->table('ve_enrollments')
                ->where(['employee_id' => $employee['id'], 'campaign_id' => $campaignId])
                ->get()->getRowArray();
            if ($enrollment === null) {
                throw new DomainException('Adesão não encontrada para este empregado.');
            }

            $service = new EnrollmentService();
            match ($action) {
                'pausar' => $service->pause((int) $enrollment['id'], $userId),
                'retomar' => $service->resume((int) $enrollment['id'], $userId),
                'encerrar' => $service->close((int) $enrollment['id'], $userId),
                default => throw new DomainException('Ação de participação inválida.'),
            };

            return redirect()->to('/vendedor-eventual')->with('success', 'Estado da participação atualizado.');
        } catch (DomainException $exception) {
            return redirect()->to('/vendedor-eventual')->with('error', $exception->getMessage());
        }
    }
}
