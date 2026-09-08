<?php

declare(strict_types=1);

use App\Database\Migrations\CreateVendedorEventualCatalogVersions;
use App\Database\Migrations\CreateVendedorEventualFoundation;
use App\Database\Migrations\CreateVendedorEventualLearningVersions;
use App\Services\AccessAdministrationService;
use App\Services\CampaignReadinessService;
use App\Services\CatalogVersionService;
use App\Services\LearningJourneyService;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Database;

require_once APPPATH . 'Database/Migrations/2026-08-25-100001_CreateVendedorEventualFoundation.php';
require_once APPPATH . 'Database/Migrations/2026-08-26-100001_CreateVendedorEventualLearningVersions.php';
require_once APPPATH . 'Database/Migrations/2026-08-26-100002_CreateVendedorEventualCatalogVersions.php';

final class CampaignReadinessServiceTest extends CIUnitTestCase
{
    private $testDb;
    private array $testMigrations;
    private int $campaignId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->testDb = Database::connect('tests');
        $this->testMigrations = [
            new CreateVendedorEventualFoundation(Database::forge('tests')),
            new CreateVendedorEventualLearningVersions(Database::forge('tests')),
            new CreateVendedorEventualCatalogVersions(Database::forge('tests')),
        ];
        foreach (array_reverse($this->testMigrations) as $migration) {
            $migration->down();
        }
        foreach ($this->testMigrations as $migration) {
            $migration->up();
        }

        $this->testDb->table('ve_campaigns')->insert([
            'code' => 'READY-01',
            'name' => 'Campanha pronta',
            'mode' => 'demonstrative',
            'status' => 'draft',
            'starts_at' => '2026-08-01 00:00:00',
            'ends_at' => '2026-12-01 00:00:00',
        ]);
        $this->campaignId = (int) $this->testDb->insertID();
    }

    protected function tearDown(): void
    {
        foreach (array_reverse($this->testMigrations) as $migration) {
            $migration->down();
        }
        parent::tearDown();
    }

    public function testCampaignCannotBePublishedWithoutCompletePackage(): void
    {
        $assessment = (new CampaignReadinessService($this->testDb))->assess($this->campaignId);

        $this->assertFalse($assessment['ready']);
        $this->assertCount(3, $assessment['issues']);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Campanha ainda não está pronta');
        (new AccessAdministrationService())->changeCampaignStatus($this->campaignId, 'published', 900);
    }

    public function testCompleteCoherentPackageAllowsPublication(): void
    {
        $this->publishPackage('Encomendas');
        $assessment = (new CampaignReadinessService($this->testDb))->assess($this->campaignId);

        $this->assertTrue($assessment['ready']);
        $this->assertSame(1, $assessment['learning_versions']);
        $this->assertSame(1, $assessment['questionnaire_versions']);
        $this->assertSame(1, $assessment['product_versions']);

        (new AccessAdministrationService())->changeCampaignStatus($this->campaignId, 'published', 900);
        $campaign = $this->testDb->table('ve_campaigns')->where('id', $this->campaignId)->get()->getRowArray();
        $this->assertSame('published', $campaign['status']);
    }

    public function testQuestionnaireCannotReferenceUnpublishedProduct(): void
    {
        $this->publishPackage('Produto inexistente');
        $assessment = (new CampaignReadinessService($this->testDb))->assess($this->campaignId);

        $this->assertFalse($assessment['ready']);
        $this->assertContains(
            'Todas as regras devem apontar somente para produtos publicados.',
            $assessment['issues']
        );
    }

    private function publishPackage(string $ruleProduct): void
    {
        $learning = new LearningJourneyService();
        $learningId = $learning->createVersion([
            'campaign_id' => $this->campaignId,
            'version' => 'v1',
            'title' => 'Capacitação',
            'training_content' => 'Conteúdo aprovado.',
            'terms_content' => 'Termos aprovados.',
            'assessment_question' => 'Conteúdo compreendido?',
            'assessment_options' => ['Sim', 'Não'],
            'correct_option' => 0,
        ], 900);
        $learning->publish($learningId);

        $catalog = new CatalogVersionService();
        $productId = $catalog->createProduct([
            'campaign_id' => $this->campaignId,
            'version' => 'v1',
            'name' => 'Encomendas',
            'problem_solved' => 'Envio de mercadorias.',
            'target_profile' => 'Pequenos negócios.',
            'benefits' => 'Distribuição nacional.',
            'restrictions' => 'Conforme regras postais.',
            'requirements' => 'CNPJ ativo.',
            'documents' => 'Documentação oficial.',
            'sales_script' => 'Roteiro aprovado.',
            'faq' => 'Perguntas aprovadas.',
        ], 900);
        $catalog->publish('produto', $productId);

        $questionnaireId = $catalog->createQuestionnaire([
            'campaign_id' => $this->campaignId,
            'version' => 'v1',
            'title' => 'Diagnóstico',
            'question_text' => ['A empresa envia produtos?'],
            'question_options' => ['Sim, Não'],
            'rule_question' => ['q1'],
            'rule_answer' => ['Sim'],
            'rule_products' => [$ruleProduct],
            'rule_reason' => ['Necessidade declarada de envios.'],
        ], 900);
        $catalog->publish('questionario', $questionnaireId);
    }
}
