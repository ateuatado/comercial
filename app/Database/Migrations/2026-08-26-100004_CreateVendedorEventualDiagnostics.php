<?php
declare(strict_types=1);
namespace App\Database\Migrations;
use CodeIgniter\Database\Migration;
class CreateVendedorEventualDiagnostics extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'=>['type'=>'INT','unsigned'=>true,'auto_increment'=>true],
            'opportunity_id'=>['type'=>'INT','unsigned'=>true],
            'questionnaire_version_id'=>['type'=>'INT','unsigned'=>true],
            'answers'=>['type'=>'TEXT'],'recommendations'=>['type'=>'TEXT'],
            'completed_by_employee_id'=>['type'=>'INT','unsigned'=>true],
            'created_at'=>['type'=>'TIMESTAMP'],
        ]);
        $this->forge->addPrimaryKey('id'); $this->forge->addUniqueKey('opportunity_id');
        $this->forge->addForeignKey('opportunity_id','ve_opportunities','id','RESTRICT','CASCADE');
        $this->forge->addForeignKey('questionnaire_version_id','ve_questionnaire_versions','id','RESTRICT','CASCADE');
        $this->forge->addForeignKey('completed_by_employee_id','employees','id','RESTRICT','CASCADE');
        $this->forge->createTable('ve_opportunity_diagnostics');
    }
    public function down(): void { $this->forge->dropTable('ve_opportunity_diagnostics',true); }
}
