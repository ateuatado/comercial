<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\EmployeeModel;
use DateTimeImmutable;
use DomainException;

class LearningJourneyService
{
    public function createVersion(array $data, int $actorUserId): int
    {
        $campaignId = (int) ($data['campaign_id'] ?? 0);
        $version = trim((string) ($data['version'] ?? ''));
        $title = trim((string) ($data['title'] ?? ''));
        $training = trim((string) ($data['training_content'] ?? ''));
        $terms = trim((string) ($data['terms_content'] ?? ''));
        $question = trim((string) ($data['assessment_question'] ?? ''));
        $options = array_values(array_filter(array_map('trim', (array) ($data['assessment_options'] ?? [])), static fn (string $value): bool => $value !== ''));
        $correct = (int) ($data['correct_option'] ?? -1);

        if ($campaignId < 1 || $version === '' || $title === '' || $training === '' || $terms === '' || $question === '' || count($options) < 2 || ! array_key_exists($correct, $options)) {
            throw new DomainException('Preencha a versão, os conteúdos e ao menos duas alternativas, indicando a resposta correta.');
        }

        $db = db_connect();
        if ($db->table('ve_campaigns')->where('id', $campaignId)->countAllResults() !== 1) {
            throw new DomainException('Campanha não encontrada.');
        }
        if ($db->table('ve_learning_versions')->where(['campaign_id' => $campaignId, 'version' => $version])->countAllResults() > 0) {
            throw new DomainException('Esta versão já existe na campanha.');
        }

        $now = date('Y-m-d H:i:s');
        $db->table('ve_learning_versions')->insert([
            'campaign_id' => $campaignId, 'version' => $version, 'title' => $title,
            'training_content' => $training, 'terms_content' => $terms,
            'assessment_question' => $question,
            'assessment_options' => json_encode($options, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            'correct_option' => $correct, 'passing_score' => 100, 'status' => 'draft',
            'created_by' => $actorUserId, 'created_at' => $now, 'updated_at' => $now,
        ]);

        return (int) $db->insertID();
    }

    public function publish(int $versionId): void
    {
        $db = db_connect();
        $version = $db->table('ve_learning_versions')->where('id', $versionId)->get()->getRowArray();
        if ($version === null || $version['status'] !== 'draft') {
            throw new DomainException('Somente uma versão em rascunho pode ser publicada.');
        }

        $now = date('Y-m-d H:i:s');
        $db->transStart();
        $db->table('ve_learning_versions')->where('campaign_id', $version['campaign_id'])->where('status', 'published')->update(['status' => 'archived', 'updated_at' => $now]);
        $db->table('ve_learning_versions')->where('id', $versionId)->update(['status' => 'published', 'published_at' => $now, 'updated_at' => $now]);
        $db->transComplete();
    }

    public function publishedFor(int $shieldUserId, int $campaignId): array
    {
        if (! (new ApplicationAccessService())->hasAccess($shieldUserId, 'vendedor_eventual', 'access', $campaignId)) {
            throw new DomainException('Campanha indisponível para este empregado.');
        }

        $employee = (new EmployeeModel())->findByShieldUserId($shieldUserId);
        $enrollment = $employee === null ? null : db_connect()->table('ve_enrollments')->where(['employee_id' => $employee['id'], 'campaign_id' => $campaignId])->get()->getRowArray();
        $version = db_connect()->table('ve_learning_versions')->where(['campaign_id' => $campaignId, 'status' => 'published'])->orderBy('published_at', 'DESC')->get()->getRowArray();
        if ($employee === null || $enrollment === null || $version === null) {
            throw new DomainException('Inicie a adesão e aguarde a publicação da capacitação.');
        }
        if (! in_array($enrollment['status'], ['started', 'in_training'], true)) {
            throw new DomainException('A capacitação não está disponível no estado atual da participação.');
        }
        $version['assessment_options'] = json_decode((string) $version['assessment_options'], true, 512, JSON_THROW_ON_ERROR);
        $version['enrollment_id'] = $enrollment['id'];
        $version['enrollment_status'] = $enrollment['status'];

        return $version;
    }

    public function complete(int $shieldUserId, int $campaignId, int $selectedOption, bool $acceptedTerms): void
    {
        $version = $this->publishedFor($shieldUserId, $campaignId);
        if (! $acceptedTerms) {
            throw new DomainException('O aceite explícito dos termos é obrigatório.');
        }
        $passed = $selectedOption === (int) $version['correct_option'];
        if (! $passed) {
            throw new DomainException('A resposta está incorreta. Revise a capacitação e tente novamente.');
        }

        (new EnrollmentService())->qualify((int) $version['enrollment_id'], [
            'terms_version' => $version['version'],
            'training_version' => $version['version'],
            'assessment_score' => 100,
            'assessment_passed' => true,
            'qualified_until' => null,
        ], new DateTimeImmutable());
    }
}
