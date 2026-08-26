<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Services\AccessAdministrationService;
use App\Services\CatalogVersionService;
use App\Services\LearningJourneyService;
use App\Services\EnrollmentService;
use CodeIgniter\HTTP\RedirectResponse;
use Config\VendedorEventual;
use DomainException;

class VendedorEventualController extends BaseController
{
    public function index(): string
    {
        $db = db_connect();
        $applications = array_map(
            static function (array $application): array {
                // PostgreSQL returns booleans as the strings "t"/"f" in result arrays.
                // The string "f" is truthy in PHP, so normalize it before rendering.
                $application['enabled'] = in_array(
                    $application['enabled'],
                    [true, 1, '1', 't', 'true'],
                    true
                );

                return $application;
            },
            $db->table('ve_applications')->orderBy('name')->get()->getResultArray()
        );
        $campaigns = $db->table('ve_campaigns')->orderBy('created_at', 'DESC')->get()->getResultArray();
        $employees = $db->table('employees')->orderBy('display_name')->get()->getResultArray();
        $entitlements = $db->table('ve_employee_entitlements ent')
            ->select('ent.*, emp.employee_id AS employee_code, emp.display_name, app.name AS application_name, campaign.name AS campaign_name')
            ->join('employees emp', 'emp.id = ent.employee_id')
            ->join('ve_applications app', 'app.id = ent.application_id')
            ->join('ve_campaigns campaign', 'campaign.id = ent.campaign_id', 'left')
            ->orderBy('ent.created_at', 'DESC')
            ->limit(100)
            ->get()->getResultArray();
        $learningVersions = $db->table('ve_learning_versions learning')
            ->select('learning.*, campaign.name AS campaign_name')
            ->join('ve_campaigns campaign', 'campaign.id = learning.campaign_id')
            ->orderBy('learning.created_at', 'DESC')->get()->getResultArray();
        $enrollments = $db->table('ve_enrollments enrollment')
            ->select('enrollment.*, emp.employee_id AS employee_code, emp.display_name, campaign.name AS campaign_name')
            ->join('employees emp', 'emp.id = enrollment.employee_id')
            ->join('ve_campaigns campaign', 'campaign.id = enrollment.campaign_id')
            ->orderBy('enrollment.updated_at', 'DESC')->limit(100)->get()->getResultArray();
        $productVersions = $db->table('ve_product_versions product')->select('product.*, campaign.name AS campaign_name')->join('ve_campaigns campaign', 'campaign.id = product.campaign_id')->orderBy('product.created_at', 'DESC')->get()->getResultArray();
        $questionnaireVersions = $db->table('ve_questionnaire_versions questionnaire')->select('questionnaire.*, campaign.name AS campaign_name')->join('ve_campaigns campaign', 'campaign.id = questionnaire.campaign_id')->orderBy('questionnaire.created_at', 'DESC')->get()->getResultArray();

        /** @var VendedorEventual $featureConfig */
        $featureConfig = config(VendedorEventual::class);

        return view('admin/vendedor_eventual/index', [
            'applications' => $applications,
            'campaigns' => $campaigns,
            'employees' => $employees,
            'entitlements' => $entitlements,
            'featureEnabled' => $featureConfig->enabled,
            'learningVersions' => $learningVersions,
            'enrollments' => $enrollments,
            'productVersions' => $productVersions,
            'questionnaireVersions' => $questionnaireVersions,
        ]);
    }

    public function createCampaign(): RedirectResponse
    {
        $code = strtoupper(trim((string) $this->request->getPost('code')));
        $name = trim((string) $this->request->getPost('name'));
        $startsAt = $this->normalizeDate((string) $this->request->getPost('starts_at'));
        $endsAt = $this->normalizeDate((string) $this->request->getPost('ends_at'));

        try {
            (new AccessAdministrationService())->createCampaign([
                'code' => $code,
                'name' => $name,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
            ], (int) auth()->user()->id);
            return redirect()->back()->with('success', 'Campanha demonstrativa criada como rascunho.');
        } catch (DomainException $exception) {
            return redirect()->back()->withInput()->with('error', $exception->getMessage());
        }
    }

    public function changeCampaignStatus(int $campaignId): RedirectResponse
    {
        try {
            (new AccessAdministrationService())->changeCampaignStatus(
                $campaignId,
                (string) $this->request->getPost('status'),
                (int) auth()->user()->id
            );
            return redirect()->back()->with('success', 'Estado da campanha atualizado.');
        } catch (DomainException $exception) {
            return redirect()->back()->with('error', $exception->getMessage());
        }
    }

    public function toggleApplication(int $applicationId): RedirectResponse
    {
        try {
            (new AccessAdministrationService())->setApplicationEnabled(
                $applicationId,
                filter_var($this->request->getPost('enabled'), FILTER_VALIDATE_BOOLEAN),
                (int) auth()->user()->id
            );
            return redirect()->back()->with('success', 'Estado da aplicação atualizado.');
        } catch (DomainException $exception) {
            return redirect()->back()->with('error', $exception->getMessage());
        }
    }

    public function grant(): RedirectResponse
    {
        try {
            (new AccessAdministrationService())->grant([
                'employee_id' => $this->request->getPost('employee_id'),
                'application_id' => $this->request->getPost('application_id'),
                'campaign_id' => $this->request->getPost('campaign_id'),
                'capability' => 'access',
                'source' => $this->request->getPost('source'),
                'valid_from' => $this->normalizeDate((string) $this->request->getPost('valid_from')) ?? date('Y-m-d H:i:s'),
                'valid_until' => $this->normalizeDate((string) $this->request->getPost('valid_until')),
                'reason' => $this->request->getPost('reason'),
            ], (int) auth()->user()->id);

            return redirect()->back()->with('success', 'Acesso concedido.');
        } catch (DomainException $exception) {
            return redirect()->back()->withInput()->with('error', $exception->getMessage());
        }
    }

    public function revoke(int $entitlementId): RedirectResponse
    {
        try {
            (new AccessAdministrationService())->revoke(
                $entitlementId,
                (int) auth()->user()->id,
                (string) $this->request->getPost('reason')
            );
            return redirect()->back()->with('success', 'Acesso revogado.');
        } catch (DomainException $exception) {
            return redirect()->back()->with('error', $exception->getMessage());
        }
    }

    public function createLearningVersion(): RedirectResponse
    {
        try {
            (new LearningJourneyService())->createVersion([
                'campaign_id' => $this->request->getPost('campaign_id'),
                'version' => $this->request->getPost('version'),
                'title' => $this->request->getPost('title'),
                'training_content' => $this->request->getPost('training_content'),
                'terms_content' => $this->request->getPost('terms_content'),
                'assessment_question' => $this->request->getPost('assessment_question'),
                'assessment_options' => $this->request->getPost('assessment_options'),
                'correct_option' => $this->request->getPost('correct_option'),
            ], (int) auth()->user()->id);

            return redirect()->back()->with('success', 'Versão criada como rascunho.');
        } catch (DomainException $exception) {
            return redirect()->back()->withInput()->with('error', $exception->getMessage());
        }
    }

    public function publishLearningVersion(int $versionId): RedirectResponse
    {
        try {
            (new LearningJourneyService())->publish($versionId);

            return redirect()->back()->with('success', 'Capacitação, avaliação e termos publicados.');
        } catch (DomainException $exception) {
            return redirect()->back()->with('error', $exception->getMessage());
        }
    }

    public function suspendEnrollment(int $enrollmentId): RedirectResponse
    {
        try {
            (new EnrollmentService())->suspend(
                $enrollmentId,
                (int) auth()->user()->id,
                (string) $this->request->getPost('reason')
            );

            return redirect()->back()->with('success', 'Participação suspensa.');
        } catch (DomainException $exception) {
            return redirect()->back()->withInput()->with('error', $exception->getMessage());
        }
    }

    public function createProductVersion(): RedirectResponse
    {
        try {
            (new CatalogVersionService())->createProduct($this->request->getPost(), (int) auth()->user()->id);
            return redirect()->back()->with('success', 'Produto salvo como rascunho versionado.');
        } catch (DomainException $exception) {
            return redirect()->back()->withInput()->with('error', $exception->getMessage());
        }
    }

    public function createQuestionnaireVersion(): RedirectResponse
    {
        try {
            (new CatalogVersionService())->createQuestionnaire($this->request->getPost(), (int) auth()->user()->id);
            return redirect()->back()->with('success', 'Questionário salvo como rascunho versionado.');
        } catch (DomainException $exception) {
            return redirect()->back()->withInput()->with('error', $exception->getMessage());
        }
    }

    public function publishCatalogVersion(string $type, int $id): RedirectResponse
    {
        try {
            (new CatalogVersionService())->publish($type, $id);
            return redirect()->back()->with('success', 'Conteúdo publicado. A versão anterior foi arquivada.');
        } catch (DomainException $exception) {
            return redirect()->back()->with('error', $exception->getMessage());
        }
    }

    private function normalizeDate(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        $timestamp = strtotime($value);
        return $timestamp === false ? null : date('Y-m-d H:i:s', $timestamp);
    }
}
