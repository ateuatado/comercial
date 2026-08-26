<?php

declare(strict_types=1);

use App\Database\Migrations\CreateVendedorEventualCatalogVersions;
use App\Database\Migrations\CreateVendedorEventualEnrollments;
use App\Database\Migrations\CreateVendedorEventualFoundation;
use App\Database\Migrations\CreateVendedorEventualOpportunities;
use App\Services\OpportunityService;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Database;
use Config\VendedorEventual;

require_once APPPATH . 'Database/Migrations/2026-08-25-100001_CreateVendedorEventualFoundation.php';
require_once APPPATH . 'Database/Migrations/2026-08-25-110001_CreateVendedorEventualEnrollments.php';
require_once APPPATH . 'Database/Migrations/2026-08-26-100002_CreateVendedorEventualCatalogVersions.php';
require_once APPPATH . 'Database/Migrations/2026-08-26-100003_CreateVendedorEventualOpportunities.php';

final class OpportunityServiceTest extends CIUnitTestCase
{
    public function testQualifiedEmployeeCreatesCorrelatedOpportunityAndImmutableInitialEvent(): void
    {
        $db = Database::connect('tests'); $foundation = new CreateVendedorEventualFoundation(Database::forge('tests')); $enrollments = new CreateVendedorEventualEnrollments(Database::forge('tests')); $catalog = new CreateVendedorEventualCatalogVersions(Database::forge('tests')); $opportunities = new CreateVendedorEventualOpportunities(Database::forge('tests'));
        $opportunities->down(); $catalog->down(); $enrollments->down(); $foundation->down(); $foundation->up(); $enrollments->up(); $catalog->up(); $opportunities->up();
        $db->table('employees')->insert(['employee_id'=>'EMP-OPP','shield_user_id'=>700,'display_name'=>'Originador','identity_source'=>'demo','employment_status'=>'active']); $employeeId=(int)$db->insertID();
        $db->table('ve_campaigns')->insert(['code'=>'OPP-1','name'=>'Oportunidades','mode'=>'demonstrative','status'=>'active','starts_at'=>'2026-08-01 00:00:00','ends_at'=>'2026-09-01 00:00:00']); $campaignId=(int)$db->insertID();
        $application=$db->table('ve_applications')->where('code','vendedor_eventual')->get()->getRowArray(); $db->table('ve_applications')->where('id',$application['id'])->update(['enabled'=>true]); config(VendedorEventual::class)->enabled=true;
        $db->table('ve_employee_entitlements')->insert(['employee_id'=>$employeeId,'application_id'=>$application['id'],'campaign_id'=>$campaignId,'capability'=>'access','source'=>'campaign','status'=>'active','valid_from'=>'2026-08-01 00:00:00','valid_until'=>'2026-09-01 00:00:00']);
        $db->table('ve_enrollments')->insert(['employee_id'=>$employeeId,'campaign_id'=>$campaignId,'status'=>'qualified','created_at'=>'2026-08-25 09:00:00','updated_at'=>'2026-08-25 09:00:00']);
        $id=(new OpportunityService())->create(700,['campaign_id'=>$campaignId,'cnpj'=>'12.345.678/0001-90','contact_context'=>'Necessidade percebida em visita.','channel'=>'presencial'],new DateTimeImmutable('2026-08-25 10:00:00'));
        $row=$db->table('ve_opportunities')->where('id',$id)->get()->getRowArray();
        $this->assertMatchesRegularExpression('/^[0-9a-f-]{36}$/',$row['correlation_id']); $this->assertSame('12345678000190',$row['cnpj']); $this->assertSame($employeeId,(int)$row['originator_employee_id']);
        $this->assertSame(1,$db->table('ve_opportunity_events')->where('opportunity_id',$id)->countAllResults());
        $detail=(new OpportunityService())->detailFor(700,$id); $this->assertCount(1,$detail['events']);
        config(VendedorEventual::class)->enabled=false; $opportunities->down(); $catalog->down(); $enrollments->down(); $foundation->down();
    }
}
