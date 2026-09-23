<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\EmployeeModel;
use DomainException;

class CatalogVersionService
{
    public function createProduct(array $data, int $actorUserId): int
    {
        $required = ['name', 'problem_solved', 'target_profile', 'benefits', 'restrictions', 'requirements', 'documents', 'sales_script', 'faq'];
        foreach ($required as $field) {
            if (trim((string) ($data[$field] ?? '')) === '') {
                throw new DomainException('Preencha todos os campos obrigatórios do produto.');
            }
        }
        $campaignId = (int) ($data['campaign_id'] ?? 0);
        $version = trim((string) ($data['version'] ?? ''));
        if ($campaignId < 1 || $version === '') {
            throw new DomainException('Informe campanha e versão do produto.');
        }
        $now = date('Y-m-d H:i:s');
        db_connect()->table('ve_product_versions')->insert([
            'campaign_id' => $campaignId, 'version' => $version, 'status' => 'draft',
            'name' => trim((string) $data['name']), 'official_name' => trim((string) ($data['official_name'] ?? '')) ?: null,
            'problem_solved' => trim((string) $data['problem_solved']), 'target_profile' => trim((string) $data['target_profile']),
            'benefits' => trim((string) $data['benefits']), 'restrictions' => trim((string) $data['restrictions']),
            'requirements' => trim((string) $data['requirements']), 'documents' => trim((string) $data['documents']),
            'sales_script' => trim((string) $data['sales_script']), 'faq' => trim((string) $data['faq']),
            'created_by' => $actorUserId, 'created_at' => $now, 'updated_at' => $now,
        ]);
        return (int) db_connect()->insertID();
    }

    public function createQuestionnaire(array $data, int $actorUserId): int
    {
        $campaignId = (int) ($data['campaign_id'] ?? 0);
        $version = trim((string) ($data['version'] ?? ''));
        $title = trim((string) ($data['title'] ?? ''));
        $questions = $this->questionnaireJson($data);
        $rules = $this->recommendationRulesJson($data);
        if ($campaignId < 1 || $version === '' || $title === '') {
            throw new DomainException('Informe campanha, versão e título do questionário.');
        }
        $now = date('Y-m-d H:i:s');
        db_connect()->table('ve_questionnaire_versions')->insert([
            'campaign_id' => $campaignId, 'version' => $version, 'title' => $title,
            'questions' => $questions, 'recommendation_rules' => $rules, 'status' => 'draft',
            'created_by' => $actorUserId, 'created_at' => $now, 'updated_at' => $now,
        ]);
        return (int) db_connect()->insertID();
    }

    public function publish(string $type, int $id): void
    {
        $table = match ($type) { 'produto' => 've_product_versions', 'questionario' => 've_questionnaire_versions', default => throw new DomainException('Tipo de conteúdo inválido.') };
        $db = db_connect();
        $row = $db->table($table)->where('id', $id)->get()->getRowArray();
        if ($row === null || $row['status'] !== 'draft') {
            throw new DomainException('Somente conteúdo em rascunho pode ser publicado.');
        }
        $now = date('Y-m-d H:i:s');
        $db->transStart();
        $previous = $db->table($table)->where('campaign_id', $row['campaign_id'])->where('status', 'published');
        if ($type === 'produto') {
            $previous->where('name', $row['name']);
        }
        $previous->update(['status' => 'archived', 'updated_at' => $now]);
        $db->table($table)->where('id', $id)->update(['status' => 'published', 'published_at' => $now, 'updated_at' => $now]);
        $db->transComplete();
    }

    public function publishedFor(int $shieldUserId, int $campaignId): array
    {
        $employee = (new EmployeeModel())->findByShieldUserId($shieldUserId);
        $enrollment = $employee === null ? null : db_connect()->table('ve_enrollments')->where(['employee_id' => $employee['id'], 'campaign_id' => $campaignId, 'status' => 'qualified'])->get()->getRowArray();
        if ($enrollment === null || ! (new ApplicationAccessService())->hasAccess($shieldUserId, 'vendedor_eventual', 'access', $campaignId)) {
            throw new DomainException('O catálogo está disponível somente para participação habilitada.');
        }
        $db = db_connect();
        return [
            'products' => $db->table('ve_product_versions')->where(['campaign_id' => $campaignId, 'status' => 'published'])->orderBy('name')->get()->getResultArray(),
            'questionnaire' => $db->table('ve_questionnaire_versions')->where(['campaign_id' => $campaignId, 'status' => 'published'])->orderBy('published_at', 'DESC')->get()->getRowArray(),
        ];
    }

    private function validatedJson(string $value, string $label): string
    {
        try { $decoded = json_decode(trim($value), true, 512, JSON_THROW_ON_ERROR); } catch (\JsonException) { throw new DomainException('Informe ' . $label . ' em JSON válido.'); }
        if (! is_array($decoded) || $decoded === []) { throw new DomainException('Informe ao menos um item em ' . $label . '.'); }
        return json_encode($decoded, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }

    private function questionnaireJson(array $data): string
    {
        if (isset($data['question_text'])) {
            $questions = [];
            foreach ((array) $data['question_text'] as $index => $text) {
                $text = trim((string) $text);
                $options = array_values(array_filter(array_map('trim', explode(',', (string) (($data['question_options'][$index] ?? ''))))));
                if ($text === '' || count($options) < 2) {
                    throw new DomainException('Cada pergunta deve possuir texto e ao menos duas alternativas separadas por vírgula.');
                }
                $questions[] = ['id' => 'q' . ($index + 1), 'text' => $text, 'options' => $options];
            }
            if ($questions === []) {
                throw new DomainException('Informe ao menos uma pergunta.');
            }
            return json_encode($questions, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        }
        return $this->validatedJson((string) ($data['questions'] ?? ''), 'perguntas');
    }

    private function recommendationRulesJson(array $data): string
    {
        if (isset($data['rule_question'])) {
            $rules = [];
            foreach ((array) $data['rule_question'] as $index => $question) {
                $question = trim((string) $question);
                $answer = trim((string) ($data['rule_answer'][$index] ?? ''));
                $products = array_values(array_filter(array_map('trim', explode(',', (string) ($data['rule_products'][$index] ?? '')))));
                $reason = trim((string) ($data['rule_reason'][$index] ?? ''));
                if ($question === '' || $answer === '' || $products === [] || $reason === '') {
                    throw new DomainException('Preencha pergunta, resposta, produtos e justificativa de cada regra.');
                }
                $rules[] = ['when' => [$question => $answer], 'products' => $products, 'reason' => $reason];
            }
            if ($rules === []) {
                throw new DomainException('Informe ao menos uma regra de recomendação.');
            }
            return json_encode($rules, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        }
        return $this->validatedJson((string) ($data['recommendation_rules'] ?? ''), 'regras de recomendação');
    }
}
