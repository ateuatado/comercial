<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Command: carteira:import-full
 *
 * Importa TODAS as 25 colunas do relatório geral de carteiras dos Correios
 * para a tabela carteira_raw.
 *
 * Este comando é idempotente: trunca a tabela antes de importar.
 * Não altera client_wallets nem vendors.
 *
 * Uso:
 *   php spark carteira:import-full [--file=/caminho/para/arquivo.csv]
 *   php spark carteira:import-full --dry-run
 */
class ImportCarteiraFull extends BaseCommand
{
    protected $group       = 'Carteira';
    protected $name        = 'carteira:import-full';
    protected $description = 'Importa todas as colunas do relatório geral de carteiras para a tabela carteira_raw.';

    protected $options = [
        '--file'    => 'Caminho completo do arquivo CSV. Padrão: ingestao/relarorio_geral_carteiras_clientes.csv',
        '--dry-run' => 'Executa sem salvar no banco (apenas mostra o que faria).',
    ];

    /** Tamanho do lote para INSERT em batch */
    private const BATCH_SIZE = 1000;

    /**
     * Mapeamento: coluna do CSV → coluna do banco.
     * A ordem corresponde à sequência das colunas no CSV.
     */
    private const CSV_TO_DB = [
        'SE'                       => 'se',
        'ID_GRUPO'                 => 'id_grupo',
        'GRUPO_CLIENTE'            => 'grupo_cliente',
        'CATEGORIA_INSTITUCIONAL'  => 'categoria',
        'CNPJ'                     => 'cnpj',
        'RAZAO_SOCIAL'             => 'razao_social',
        'SEGMENTO_DE_CLIENTE'      => 'segmento_cliente',
        'SEGMENTO_DE_MERCADO'      => 'segmento_mercado',
        'CANAIS_VENDAS'            => 'canais_vendas',
        'CANAIS_VENDAS_OBSERVACAO' => 'canais_vendas_obs',
        'PROSPECCAO'               => 'prospeccao',
        'FORCA_VENDAS_NOME'        => 'forca_vendas_nome',
        'MATRICULA_MCMCU'          => 'matricula_mcmcu',
        'CONTA_NUMERO'             => 'conta_numero',
        'CONTA_NOME'               => 'conta_nome',
        'CICLO_DE_VIDA'            => 'ciclo_de_vida',
        'CNAE'                     => 'cnae',
        'CNAE_DESC'                => 'cnae_desc',
        'SEG_MERC_CNAE'            => 'seg_merc_cnae',
        'NAT_JURIDICA'             => 'nat_juridica',
        'GERENCIA'                 => 'gerencia',
        'MTR_COOD'                 => 'mtr_cood',
        'NOME_COOD'                => 'nome_cood',
        'GERENCIA_VENDAS'          => 'gerencia_vendas',
        'FORCA_VENDAS_EMAIL'       => 'forca_vendas_email',
    ];

    public function run(array $params): void
    {
        $db      = db_connect();
        $dryRun  = array_key_exists('dry-run', CLI::getOptions());
        $csvPath = CLI::getOption('file')
            ?? ROOTPATH . 'ingestao/relarorio_geral_carteiras_clientes.csv';

        if (! file_exists($csvPath)) {
            CLI::error("Arquivo não encontrado: {$csvPath}");
            return;
        }

        CLI::write('');
        CLI::write('╔══════════════════════════════════════════════════════════╗', 'cyan');
        CLI::write('║   SPIV — Importação Completa de Carteira (carteira_raw)  ║', 'cyan');
        CLI::write('╚══════════════════════════════════════════════════════════╝', 'cyan');
        CLI::write("Arquivo : {$csvPath}");
        CLI::write('Modo    : ' . ($dryRun ? 'DRY-RUN (nenhum dado será salvo)' : 'PRODUÇÃO'));
        CLI::write('');

        // ─────────────────────────────────────────────────────────────────
        // FASE 0: Contar linhas
        // ─────────────────────────────────────────────────────────────────
        CLI::write('[0/3] Contando linhas do arquivo...', 'yellow');
        $totalLines = 0;
        $fCount = fopen($csvPath, 'r');
        fgets($fCount); // pula cabeçalho
        while (fgets($fCount) !== false) {
            $totalLines++;
        }
        fclose($fCount);
        CLI::write('      Total de registros: ' . number_format($totalLines, 0, ',', '.'), 'green');
        CLI::write('');

        // ─────────────────────────────────────────────────────────────────
        // FASE 1: Truncar tabela (importação idempotente)
        // ─────────────────────────────────────────────────────────────────
        CLI::write('[1/3] Preparando tabela carteira_raw...', 'yellow');
        if (! $dryRun) {
            $db->query('TRUNCATE TABLE carteira_raw RESTART IDENTITY');
            CLI::write('      ✔ Tabela truncada.', 'green');
        } else {
            CLI::write('      [DRY-RUN] Tabela não foi truncada.', 'dark_gray');
        }
        CLI::write('');

        // ─────────────────────────────────────────────────────────────────
        // FASE 2: Importar CSV em lotes
        // ─────────────────────────────────────────────────────────────────
        CLI::write('[2/3] Importando dados...', 'yellow');

        $fh = fopen($csvPath, 'r');
        $headerRaw = fgetcsv($fh, 0, ';');
        $header = array_map(function ($v) {
            return mb_convert_encoding(trim($v), 'UTF-8', 'ISO-8859-1');
        }, $headerRaw);

        // Validar que as colunas esperadas existem
        $csvMap = self::CSV_TO_DB;
        $colIndexes = [];
        foreach ($csvMap as $csvCol => $dbCol) {
            $idx = array_search($csvCol, $header);
            if ($idx === false) {
                CLI::error("Coluna esperada não encontrada no CSV: {$csvCol}");
                fclose($fh);
                return;
            }
            $colIndexes[$csvCol] = $idx;
        }

        $batch      = [];
        $processed  = 0;
        $skipped    = 0;
        $inserted   = 0;
        $now        = date('Y-m-d H:i:s');
        $lastPct    = -1;

        while (($row = fgetcsv($fh, 0, ';')) !== false) {
            if (count($row) < count($header)) {
                $skipped++;
                continue;
            }

            // Converte Latin-1 → UTF-8
            $row = array_map(function ($v) {
                return mb_convert_encoding(trim($v), 'UTF-8', 'ISO-8859-1');
            }, $row);

            $processed++;

            // Mapeia CSV → DB
            $record = ['created_at' => $now];
            foreach ($csvMap as $csvCol => $dbCol) {
                $val = $row[$colIndexes[$csvCol]] ?? '';
                // Campos vazios viram NULL (exceto CNPJ que é obrigatório)
                if ($val === '' && $dbCol !== 'cnpj') {
                    $record[$dbCol] = null;
                } else {
                    $record[$dbCol] = $val;
                }
            }

            // Validar CNPJ mínimo
            $cnpj = preg_replace('/[^0-9]/', '', $record['cnpj']);
            if (strlen($cnpj) < 8) {
                $skipped++;
                continue;
            }
            $record['cnpj'] = str_pad($cnpj, 14, '0', STR_PAD_LEFT);

            $batch[] = $record;

            // Flush do lote
            if (count($batch) >= self::BATCH_SIZE) {
                if (! $dryRun) {
                    $db->table('carteira_raw')->insertBatch($batch);
                }
                $inserted += count($batch);
                $batch = [];

                // Progresso (a cada 5%)
                $pct = (int) round(($processed / $totalLines) * 100);
                if ($pct >= $lastPct + 5) {
                    $lastPct = $pct;
                    CLI::write(sprintf(
                        '      %s / %s (%d%%)...',
                        number_format($processed, 0, ',', '.'),
                        number_format($totalLines, 0, ',', '.'),
                        $pct
                    ));
                }
            }
        }
        fclose($fh);

        // Flush final
        if (! empty($batch)) {
            if (! $dryRun) {
                $db->table('carteira_raw')->insertBatch($batch);
            }
            $inserted += count($batch);
        }

        CLI::write('');

        // ─────────────────────────────────────────────────────────────────
        // FASE 3: Resumo
        // ─────────────────────────────────────────────────────────────────
        CLI::write('[3/3] Concluído!', 'yellow');
        CLI::write('');
        CLI::write('╔══════════════════════════════════════════════════════════╗', 'green');
        CLI::write('║                     RESUMO FINAL                         ║', 'green');
        CLI::write('╠══════════════════════════════════════════════════════════╣', 'green');
        CLI::write(sprintf('║  Linhas processadas  : %s', str_pad(number_format($processed, 0, ',', '.'), 30)), 'green');
        CLI::write(sprintf('║  Registros inseridos : %s', str_pad(number_format($inserted, 0, ',', '.'), 30)), 'green');
        CLI::write(sprintf('║  Linhas ignoradas    : %s', str_pad(number_format($skipped, 0, ',', '.'), 30)), 'green');
        CLI::write('╚══════════════════════════════════════════════════════════╝', 'green');
        CLI::write('');

        if ($dryRun) {
            CLI::write('⚠  DRY-RUN: Nenhum dado foi persistido no banco de dados.', 'yellow');
        } else {
            // Verificação rápida
            $count = (int) $db->query('SELECT COUNT(*) AS c FROM carteira_raw')->getRow()->c;
            CLI::write("✔  Importação concluída! Total na tabela: " . number_format($count, 0, ',', '.'), 'green');
        }
        CLI::write('');
    }
}
