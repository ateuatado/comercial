<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class IngestReceita extends BaseCommand
{
    protected $group       = 'App';
    protected $name        = 'receita:ingest';
    protected $description = 'Ingere CNPJs do schema/arquivos da Receita para public.client_wallets (Janela de 2 anos, Ativos)';
    protected $usage       = 'receita:ingest [options]';
    protected $arguments   = [];
    protected $options     = [];

    public function run(array $params)
    {
        $db = \Config\Database::connect();
        
        $tempDir = 'C:\\xampp\\htdocs\\spiv\\adm\\temp_extract';
        $cutoffDate = '20240707'; // Janela de 2 anos (a partir de 2026-07-07)
        
        CLI::write("Iniciando varredura de arquivos *ESTABELE em: {$tempDir}", 'cyan');
        
        $files = glob($tempDir . DIRECTORY_SEPARATOR . '*ESTABELE');
        if (empty($files)) {
            CLI::error("Nenhum arquivo *ESTABELE encontrado em {$tempDir}");
            return;
        }
        
        CLI::write("Encontrados " . count($files) . " arquivos para processamento.", 'yellow');
        
        // Criar arquivo temporário para os CNPJs filtrados
        $csvPath = $tempDir . DIRECTORY_SEPARATOR . 'filtered_cnpjs_for_copy.csv';
        $csvHandle = fopen($csvPath, 'w');
        if (!$csvHandle) {
            CLI::error("Falha ao criar arquivo CSV temporário em {$csvPath}");
            return;
        }
        
        $totalProcessed = 0;
        $totalMatched = 0;
        $now = date('Y-m-d H:i:s');
        
        foreach ($files as $file) {
            CLI::write("Processando arquivo: " . basename($file), 'cyan');
            $handle = fopen($file, 'r');
            if (!$handle) {
                CLI::error("Não foi possível abrir o arquivo: {$file}");
                continue;
            }
            
            $fileProcessed = 0;
            $fileMatched = 0;
            
            while (($row = fgetcsv($handle, 0, ';')) !== false) {
                $fileProcessed++;
                $totalProcessed++;
                
                // Mapeamento de colunas do layout RFB para estabelecimentos:
                // 0: cnpj_basico
                // 1: cnpj_ordem
                // 2: cnpj_dv
                // 5: situacao_cadastral ('02' = ativa)
                // 10: data_inicio_atividade (YYYYMMDD)
                
                if (count($row) < 11) {
                    continue;
                }
                
                $cnpjBasico = $row[0];
                $cnpjOrdem = $row[1];
                $cnpjDv = $row[2];
                $situacao = $row[5];
                $dataInicio = $row[10];
                
                // Filtro: data_inicio_atividade >= 20240707 e situacao_cadastral = '02' (Ativa)
                if ($dataInicio >= $cutoffDate && $situacao === '02') {
                    $cnpj = $cnpjBasico . $cnpjOrdem . $cnpjDv;
                    // Escrever no CSV de saída: cnpj, status_operacional, created_at, updated_at
                    fputcsv($csvHandle, [$cnpj, 'novo', $now, $now]);
                    $fileMatched++;
                    $totalMatched++;
                }
                
                if ($fileProcessed % 500000 === 0) {
                    CLI::write("  Lidos: {$fileProcessed} | Casados: {$fileMatched}", 'light_gray');
                }
            }
            
            fclose($handle);
            CLI::write("Finalizado " . basename($file) . " | Lidos: {$fileProcessed} | Casados: {$fileMatched}", 'green');
        }
        
        fclose($csvHandle);
        CLI::write("Varredura concluída. Total Lidos: {$totalProcessed} | Total Casados: {$totalMatched}", 'yellow');
        
        if ($totalMatched === 0) {
            CLI::write("Nenhum CNPJ correspondeu aos critérios de filtro. Abortando importação no DB.", 'red');
            @unlink($csvPath);
            return;
        }
        
        // Importação ultra rápida via staging table no PostgreSQL
        CLI::write("Iniciando importação no PostgreSQL via COPY...", 'cyan');
        
        try {
            // 1. Criar tabela de staging temporária
            $db->query("DROP TABLE IF EXISTS public.stg_client_wallets;");
            $db->query("
                CREATE TABLE public.stg_client_wallets (
                    cnpj VARCHAR(14),
                    status_operacional VARCHAR(30),
                    created_at TIMESTAMP,
                    updated_at TIMESTAMP
                );
            ");
            
            // 2. Executar COPY usando psql ou query direta se superuser.
            // Como estamos no Windows e psql está disponível, vamos usar o COPY direto do SQL
            // pois o postgres roda localmente e o arquivo está acessível a ele.
            // Para garantir barras corretas no Windows para o PostgreSQL:
            $escapedCsvPath = str_replace('\\', '/', $csvPath);
            
            CLI::write("Executando COPY a partir de: {$escapedCsvPath}", 'yellow');
            $db->query("COPY public.stg_client_wallets (cnpj, status_operacional, created_at, updated_at) FROM '{$escapedCsvPath}' WITH (FORMAT csv, DELIMITER ',');");
            
            // 3. Mover do staging para client_wallets evitando duplicatas
            CLI::write("Movendo dados para public.client_wallets com ON CONFLICT DO NOTHING...", 'cyan');
            $db->query("
                INSERT INTO public.client_wallets (cnpj, status_operacional, created_at, updated_at)
                SELECT cnpj, status_operacional, created_at, updated_at 
                FROM public.stg_client_wallets
                ON CONFLICT (cnpj) DO NOTHING;
            ");
            
            // 4. Limpar staging
            $db->query("DROP TABLE IF EXISTS public.stg_client_wallets;");
            
            CLI::write("Importação concluída com sucesso no banco de dados!", 'green');
            
        } catch (\Exception $e) {
            CLI::error("Erro durante a importação no banco de dados: " . $e->getMessage());
        }
        
        // Deletar o CSV temporário
        @unlink($csvPath);
        
        // Mostrar contagem final na tabela
        $count = $db->query("SELECT COUNT(*) as total FROM public.client_wallets")->getRowArray();
        CLI::write("Total de registros atuais em public.client_wallets: " . $count['total'], 'green');
    }
}
