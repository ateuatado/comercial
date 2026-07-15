<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class SeedDemoData extends BaseCommand
{
    protected $group       = 'SPIV';
    protected $name        = 'spiv:demo-seed';
    protected $description = 'Limpa tabelas vitais e popula o banco com dados puramente fictícios para demonstração.';

    public function run(array $params)
    {
        CLI::write("=== Geração de Dados de Demonstração (Fictícios) ===", 'yellow');
        CLI::write("AVISO: Este comando irá LIMPAR (truncate) os dados atuais das tabelas de carteira.", 'red');
        CLI::write("Ele DEVE ser rodado APENAS em bancos de demonstração (ex: spivvps).", 'red');
        
        $confirm = CLI::prompt("Você tem CERTEZA que deseja continuar?", ['y', 'n'], 'required');

        if (strtolower($confirm) !== 'y') {
            CLI::write("Operação cancelada pelo usuário.", 'green');
            return;
        }

        CLI::write("\nRodando seeder DemoDataSeeder...", 'cyan');
        
        try {
            $seeder = \Config\Database::seeder();
            $seeder->call('DemoDataSeeder');
            CLI::write("Finalizado com sucesso!", 'green');
        } catch (\Exception $e) {
            CLI::error("Ocorreu um erro durante a inserção de dados falsos:");
            CLI::error($e->getMessage());
        }
    }
}
