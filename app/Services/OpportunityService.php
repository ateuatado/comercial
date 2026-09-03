<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\EmployeeModel;
use DateTimeImmutable;
use DateTimeInterface;
use DomainException;

class OpportunityService
{
    public function create(int $shieldUserId, array $data, ?DateTimeInterface $at = null): int
    {
        $employee = (new EmployeeModel())->findByShieldUserId($shieldUserId);
        $campaignId = (int) ($data['campaign_id'] ?? 0);
        $cnpj = preg_replace('/\D/', '', (string) ($data['cnpj'] ?? ''));
        $context = trim((string) ($data['contact_context'] ?? ''));
        $channel = (string) ($data['channel'] ?? 'presencial');
        if ($employee === null || $employee['employment_status'] !== 'active' || $campaignId < 1) {
            throw new DomainException('Empregado ou campanha inválidos.');
        }
        if (strlen($cnpj) !== 14 || preg_match('/^(\d)\1{13}$/', $cnpj)) {
            throw new DomainException('Informe um CNPJ com 14 dígitos.');
        }
        if ($context === '' || ! in_array($channel, ['presencial', 'telefone', 'email', 'evento', 'outro'], true)) {
            throw new DomainException('Informe o contexto do contato e um canal válido.');
        }
        $enrollment = db_connect()->table('ve_enrollments')->where(['employee_id' => $employee['id'], 'campaign_id' => $campaignId, 'status' => 'qualified'])->get()->getRowArray();
        if ($enrollment === null || ! (new ApplicationAccessService())->hasAccess($shieldUserId, 'vendedor_eventual', 'access', $campaignId)) {
            throw new DomainException('Somente participação habilitada pode registrar oportunidade.');
        }
        $questionnaire = db_connect()->table('ve_questionnaire_versions')->where(['campaign_id' => $campaignId, 'status' => 'published'])->orderBy('published_at', 'DESC')->get()->getRowArray();
        $instant = $at ?? new DateTimeImmutable();
        $correlationId = $this->uuid();
        $db = db_connect();
        $db->transStart();
        $db->table('ve_opportunities')->insert([
            'correlation_id' => $correlationId, 'campaign_id' => $campaignId,
            'originator_employee_id' => $employee['id'], 'current_conductor_employee_id' => $employee['id'],
            'questionnaire_version_id' => $questionnaire['id'] ?? null, 'cnpj' => $cnpj,
            'contact_context' => $context, 'channel' => $channel, 'status' => 'registered',
            'contacted_at' => $instant->format('Y-m-d H:i:s'), 'created_at' => $instant->format('Y-m-d H:i:s'), 'updated_at' => $instant->format('Y-m-d H:i:s'),
        ]);
        $id = (int) $db->insertID();
        $this->appendEvent($db, $id, 'opportunity_registered', (int) $employee['id'], $shieldUserId, $channel, $instant, $questionnaire['version'] ?? null, ['correlation_id' => $correlationId, 'cnpj' => $cnpj]);
        $db->transComplete();
        if (! $db->transStatus()) { throw new DomainException('Não foi possível registrar a oportunidade.'); }
        return $id;
    }

    public function mine(int $shieldUserId): array
    {
        $employee = (new EmployeeModel())->findByShieldUserId($shieldUserId);
        if ($employee === null) { return []; }
        return db_connect()->table('ve_opportunities opportunity')->select('opportunity.*, campaign.name AS campaign_name')->join('ve_campaigns campaign', 'campaign.id = opportunity.campaign_id')->where('opportunity.originator_employee_id', $employee['id'])->orderBy('opportunity.created_at', 'DESC')->get()->getResultArray();
    }

    public function detailFor(int $shieldUserId, int $opportunityId): array
    {
        $employee = (new EmployeeModel())->findByShieldUserId($shieldUserId);
        $opportunity = $employee === null ? null : db_connect()->table('ve_opportunities opportunity')->select('opportunity.*, campaign.name AS campaign_name, questionnaire.version AS questionnaire_version')->join('ve_campaigns campaign', 'campaign.id = opportunity.campaign_id')->join('ve_questionnaire_versions questionnaire', 'questionnaire.id = opportunity.questionnaire_version_id', 'left')->where(['opportunity.id' => $opportunityId, 'opportunity.originator_employee_id' => $employee['id']])->get()->getRowArray();
        if ($opportunity === null) { throw new DomainException('Oportunidade não encontrada para este empregado.'); }
        $opportunity['events'] = db_connect()->table('ve_opportunity_events')->where('opportunity_id', $opportunityId)->orderBy('occurred_at', 'ASC')->get()->getResultArray();
        $opportunity['portfolio'] = (new PortfolioVisibilityService())->statusForCnpj((string) $opportunity['cnpj']);
        $opportunity['portfolio_request'] = db_connect()->tableExists('ve_portfolio_requests')
            ? db_connect()->table('ve_portfolio_requests request')
                ->select('request.*, reservation.reservation_id AS reservation_reference, reservation.status AS reservation_status')
                ->join('ve_portfolio_reservations reservation', 'reservation.id = request.reservation_id', 'left')
                ->where('request.opportunity_id', $opportunityId)
                ->get()
                ->getRowArray()
            : null;
        $diagnostic = db_connect()->tableExists('ve_opportunity_diagnostics') ? db_connect()->table('ve_opportunity_diagnostics')->where('opportunity_id', $opportunityId)->get()->getRowArray() : null;
        if ($diagnostic !== null) { $diagnostic['answers'] = json_decode($diagnostic['answers'], true); $diagnostic['recommendations'] = json_decode($diagnostic['recommendations'], true); }
        $opportunity['diagnostic'] = $diagnostic;
        return $opportunity;
    }

    public function diagnosticFormFor(int $shieldUserId, int $opportunityId): array
    {
        $opportunity = $this->detailFor($shieldUserId, $opportunityId);
        if ($opportunity['diagnostic'] !== null) { throw new DomainException('O diagnóstico desta oportunidade já foi concluído.'); }
        if ($opportunity['questionnaire_version_id'] === null) { throw new DomainException('A oportunidade não possui questionário versionado vinculado.'); }
        $questionnaire = db_connect()->table('ve_questionnaire_versions')->where('id', $opportunity['questionnaire_version_id'])->get()->getRowArray();
        if ($questionnaire === null) { throw new DomainException('Questionário não localizado.'); }
        $questionnaire['questions'] = json_decode($questionnaire['questions'], true, 512, JSON_THROW_ON_ERROR);
        return ['opportunity' => $opportunity, 'questionnaire' => $questionnaire];
    }

    public function completeDiagnostic(int $shieldUserId, int $opportunityId, array $submittedAnswers): void
    {
        $form = $this->diagnosticFormFor($shieldUserId, $opportunityId); $questions = $form['questionnaire']['questions']; $answers = [];
        foreach ($questions as $question) { $id=(string)$question['id']; $answer=(string)($submittedAnswers[$id]??''); if (!in_array($answer,(array)$question['options'],true)) { throw new DomainException('Responda todas as perguntas usando as alternativas disponíveis.'); } $answers[$id]=$answer; }
        $rules=json_decode($form['questionnaire']['recommendation_rules'],true,512,JSON_THROW_ON_ERROR); $recommendations=[];
        $publishedNames=array_column(db_connect()->table('ve_product_versions')->select('name')->where(['campaign_id'=>$form['opportunity']['campaign_id'],'status'=>'published'])->get()->getResultArray(),'name');
        foreach ($rules as $rule) { $matches=true; foreach ((array)($rule['when']??[]) as $question=>$expected) { if (($answers[$question]??null)!==$expected) {$matches=false; break;} } if(!$matches)continue; foreach((array)($rule['products']??[]) as $product){ if(in_array($product,$publishedNames,true)&&!isset($recommendations[$product])){$recommendations[$product]=['product'=>$product,'reason'=>(string)($rule['reason']??'Regra publicada do questionário.')];} if(count($recommendations)>=3)break 2;} }
        $employee=(new EmployeeModel())->findByShieldUserId($shieldUserId); $now=new DateTimeImmutable(); $db=db_connect(); $db->transStart();
        $db->table('ve_opportunity_diagnostics')->insert(['opportunity_id'=>$opportunityId,'questionnaire_version_id'=>$form['questionnaire']['id'],'answers'=>json_encode($answers,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR),'recommendations'=>json_encode(array_values($recommendations),JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR),'completed_by_employee_id'=>$employee['id'],'created_at'=>$now->format('Y-m-d H:i:s')]);
        $db->table('ve_opportunities')->where('id',$opportunityId)->update(['status'=>'diagnosis','updated_at'=>$now->format('Y-m-d H:i:s')]);
        $this->appendEvent($db,$opportunityId,'diagnostic_completed',(int)$employee['id'],$shieldUserId,'system',$now,$form['questionnaire']['version'],['recommendation_count'=>count($recommendations)]); $db->transComplete();
        if(!$db->transStatus()){throw new DomainException('Não foi possível concluir o diagnóstico.');}
    }

    private function appendEvent($db, int $opportunityId, string $type, int $employeeId, int $userId, string $channel, DateTimeInterface $at, ?string $version, array $metadata): void
    {
        $db->table('ve_opportunity_events')->insert(['opportunity_id' => $opportunityId, 'event_id' => $this->uuid(), 'event_type' => $type, 'actor_employee_id' => $employeeId, 'actor_user_id' => $userId, 'channel' => $channel, 'occurred_at' => $at->format('Y-m-d H:i:s'), 'received_at' => (new DateTimeImmutable())->format('Y-m-d H:i:s'), 'content_version' => $version, 'metadata' => json_encode($metadata, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)]);
    }

    private function uuid(): string
    {
        $bytes = random_bytes(16); $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40); $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80); $hex = bin2hex($bytes);
        return substr($hex, 0, 8) . '-' . substr($hex, 8, 4) . '-' . substr($hex, 12, 4) . '-' . substr($hex, 16, 4) . '-' . substr($hex, 20);
    }
}
