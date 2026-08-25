<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Services\AccessAdministrationService;
use CodeIgniter\HTTP\RedirectResponse;
use DomainException;

class VendedorEventualController extends BaseController
{
    public function index(): string
    {
        $db = db_connect();
        $applications = $db->table('ve_applications')->orderBy('name')->get()->getResultArray();
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

        return view('admin/vendedor_eventual/index', compact('applications', 'campaigns', 'employees', 'entitlements'));
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
