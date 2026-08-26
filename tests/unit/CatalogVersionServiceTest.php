<?php

declare(strict_types=1);

use App\Database\Migrations\CreateVendedorEventualCatalogVersions;
use App\Database\Migrations\CreateVendedorEventualEnrollments;
use App\Database\Migrations\CreateVendedorEventualFoundation;
use App\Services\CatalogVersionService;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Database;

require_once APPPATH . 'Database/Migrations/2026-08-25-100001_CreateVendedorEventualFoundation.php';
require_once APPPATH . 'Database/Migrations/2026-08-25-110001_CreateVendedorEventualEnrollments.php';
require_once APPPATH . 'Database/Migrations/2026-08-26-100002_CreateVendedorEventualCatalogVersions.php';

final class CatalogVersionServiceTest extends CIUnitTestCase
{
    public function testProductAndQuestionnaireAreCreatedAsDraftAndPublishedExplicitly(): void
    {
        $db = Database::connect('tests');
        $foundation = new CreateVendedorEventualFoundation(Database::forge('tests'));
        $enrollments = new CreateVendedorEventualEnrollments(Database::forge('tests'));
        $catalog = new CreateVendedorEventualCatalogVersions(Database::forge('tests'));
        $catalog->down(); $enrollments->down(); $foundation->down();
        $foundation->up(); $enrollments->up(); $catalog->up();
        $db->table('ve_campaigns')->insert(['code' => 'CAT-1', 'name' => 'Catálogo', 'mode' => 'demonstrative', 'status' => 'active']);
        $campaignId = (int) $db->insertID();
        $service = new CatalogVersionService();
        $productId = $service->createProduct(['campaign_id' => $campaignId, 'version' => 'v1', 'name' => 'Produto validado', 'problem_solved' => 'Problema', 'target_profile' => 'Perfil', 'benefits' => 'Benefícios', 'restrictions' => 'Restrições', 'requirements' => 'Requisitos', 'documents' => 'Documentos', 'sales_script' => 'Roteiro', 'faq' => 'FAQ'], 1);
        $questionnaireId = $service->createQuestionnaire(['campaign_id' => $campaignId, 'version' => 'v1', 'title' => 'Diagnóstico', 'questions' => '[{"id":"q1"}]', 'recommendation_rules' => '[{"when":{"q1":"sim"}}]'], 1);
        $this->assertSame('draft', $db->table('ve_product_versions')->where('id', $productId)->get()->getRow('status'));
        $service->publish('produto', $productId); $service->publish('questionario', $questionnaireId);
        $this->assertSame('published', $db->table('ve_product_versions')->where('id', $productId)->get()->getRow('status'));
        $this->assertSame('published', $db->table('ve_questionnaire_versions')->where('id', $questionnaireId)->get()->getRow('status'));
        $catalog->down(); $enrollments->down(); $foundation->down();
    }
}
