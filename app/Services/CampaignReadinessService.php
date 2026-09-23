<?php

declare(strict_types=1);

namespace App\Services;

use CodeIgniter\Database\BaseConnection;
use DomainException;
use JsonException;

/** Valida o pacote publicável de uma campanha antes de expô-la aos empregados. */
class CampaignReadinessService
{
    public function __construct(private ?BaseConnection $db = null)
    {
        $this->db ??= db_connect();
    }

    /** @return array{ready: bool, issues: list<string>, learning_versions: int, questionnaire_versions: int, product_versions: int} */
    public function assess(int $campaignId): array
    {
        $issues = [];
        $campaign = $this->db->table('ve_campaigns')->where('id', $campaignId)->get()->getRowArray();

        if ($campaign === null) {
            return [
                'ready' => false,
                'issues' => ['Campanha não encontrada.'],
                'learning_versions' => 0,
                'questionnaire_versions' => 0,
                'product_versions' => 0,
            ];
        }

        $learningCount = $this->db->table('ve_learning_versions')
            ->where(['campaign_id' => $campaignId, 'status' => 'published'])
            ->countAllResults();
        $questionnaires = $this->db->table('ve_questionnaire_versions')
            ->where(['campaign_id' => $campaignId, 'status' => 'published'])
            ->get()->getResultArray();
        $products = $this->db->table('ve_product_versions')
            ->select('name')
            ->where(['campaign_id' => $campaignId, 'status' => 'published'])
            ->get()->getResultArray();

        if ($learningCount !== 1) {
            $issues[] = 'Publique exatamente uma versão de capacitação e termos.';
        }
        if (count($questionnaires) !== 1) {
            $issues[] = 'Publique exatamente uma versão de questionário.';
        }
        if (count($products) < 1 || count($products) > 3) {
            $issues[] = 'Publique de um a três produtos na campanha.';
        }

        $productNames = array_values(array_unique(array_map(
            static fn (array $product): string => trim((string) $product['name']),
            $products
        )));
        if (count($productNames) !== count($products)) {
            $issues[] = 'Os produtos publicados devem possuir nomes únicos.';
        }

        if (count($questionnaires) === 1) {
            $issues = array_merge($issues, $this->questionnaireIssues($questionnaires[0], $productNames));
        }

        return [
            'ready' => $issues === [],
            'issues' => $issues,
            'learning_versions' => $learningCount,
            'questionnaire_versions' => count($questionnaires),
            'product_versions' => count($products),
        ];
    }

    public function assertReady(int $campaignId): void
    {
        $assessment = $this->assess($campaignId);
        if (! $assessment['ready']) {
            throw new DomainException('Campanha ainda não está pronta: ' . implode(' ', $assessment['issues']));
        }
    }

    /** @param list<string> $productNames @return list<string> */
    private function questionnaireIssues(array $questionnaire, array $productNames): array
    {
        try {
            $questions = json_decode((string) $questionnaire['questions'], true, 512, JSON_THROW_ON_ERROR);
            $rules = json_decode((string) $questionnaire['recommendation_rules'], true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return ['O questionário publicado contém JSON inválido.'];
        }

        if (! is_array($questions) || $questions === [] || ! is_array($rules) || $rules === []) {
            return ['O questionário publicado deve conter perguntas e regras de recomendação.'];
        }

        foreach ($rules as $rule) {
            foreach ((array) ($rule['products'] ?? []) as $product) {
                if (! in_array(trim((string) $product), $productNames, true)) {
                    return ['Todas as regras devem apontar somente para produtos publicados.'];
                }
            }
        }

        return [];
    }
}
