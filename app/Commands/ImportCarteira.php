<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Command: carteira:import
 *
 * Importa o relatório geral de carteiras de clientes dos Correios para o SPIV.
 *
 * O processo é feito em 3 fases:
 *   1. Cria/atualiza os vendedores (ACOMs) ausentes na tabela vendors.
 *   2. Faz UPSERT dos CNPJs na tabela client_wallets com o status e vendor corretos.
 *   3. Registra um movimento de "carga_inicial" em wallet_movements para auditoria.
 *
 * Mapeamento de status_operacional a partir do CSV:
 *   - CICLO_DE_VIDA = "Fidelização"  + CONTA_NUMERO preenchida → convertido
 *   - CICLO_DE_VIDA = "Fidelização"  + sem conta               → em_acompanhamento
 *   - CICLO_DE_VIDA = "Prospecção"   OU PROSPECCAO = "SIM"     → novo
 *   - CICLO_DE_VIDA = "A Prospectar"                           → novo
 *   - CICLO_DE_VIDA = "Recuperação"  OU "Declínio"             → inativo
 *   - CICLO_DE_VIDA = "Rentabilização"                         → em_acompanhamento
 *
 * Uso:
 *   php spark carteira:import [--file=/caminho/para/arquivo.csv]
 */
class ImportCarteira extends BaseCommand
{
    protected $group       = 'Carteira';
    protected $name        = 'carteira:import';
    protected $description = 'Importa o relatório geral de carteiras de clientes dos Correios para o SPIV.';

    protected $options = [
        '--file' => 'Caminho completo do arquivo CSV. Padrão: ingestao/relarorio_geral_carteiras_clientes.csv',
        '--dry-run' => 'Executa sem salvar no banco (apenas mostra o que faria).',
    ];

    /** Tamanho do lote para INSERT/UPSERT em batch */
    private const BATCH_SIZE = 500;

    /** ID de sistema usado em wallet_movements.realizado_por quando é carga automática */
    private const SYSTEM_USER_ID = null;

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
        CLI::write('╔══════════════════════════════════════════════════════╗', 'cyan');
        CLI::write('║       SPIV — Importação de Carteira Nacional         ║', 'cyan');
        CLI::write('╚══════════════════════════════════════════════════════╝', 'cyan');
        CLI::write("Arquivo : {$csvPath}");
        CLI::write('Modo    : ' . ($dryRun ? 'DRY-RUN (nenhum dado será salvo)' : 'PRODUÇÃO'));
        CLI::write('');

        // ─────────────────────────────────────────────────────────────────────
        // FASE 0: Contar linhas para a barra de progresso
        // ─────────────────────────────────────────────────────────────────────
        CLI::write('[0/3] Contando linhas do arquivo...', 'yellow');
        $totalLines = 0;
        $fCount = fopen($csvPath, 'r');
        fgets($fCount); // pula cabeçalho
        while (fgets($fCount) !== false) {
            $totalLines++;
        }
        fclose($fCount);
        CLI::write("      Total de registros: " . number_format($totalLines, 0, ',', '.'), 'green');
        CLI::write('');

        // ─────────────────────────────────────────────────────────────────────
        // FASE 1: Carregar mapa de vendors existentes e pré-processar novos
        // ─────────────────────────────────────────────────────────────────────
        CLI::write('[1/3] Processando vendedores (ACOMs)...', 'yellow');

        // Carrega vendors existentes: matricula => id
        $vendorMap = [];
        foreach ($db->query("SELECT id, matricula FROM vendors")->getResultArray() as $row) {
            $vendorMap[trim($row['matricula'])] = (int) $row['id'];
        }

        // Primeira passagem: coleta todos os ACOMs únicos do CSV
        $novosVendors = [];
        $fh = fopen($csvPath, 'r');
        $header = fgetcsv($fh, 0, ';');
        $header = array_map('trim', $header);

        while (($row = fgetcsv($fh, 0, ';')) !== false) {
            if (count($row) < count($header)) continue;
            // Converte Latin-1 → UTF-8 (o CSV dos Correios é gerado em ISO-8859-1)
            $row = array_map(fn($v) => mb_convert_encoding($v, 'UTF-8', 'ISO-8859-1'), $row);
            $data = array_combine($header, $row);

            $matricula = trim($data['MATRICULA_MCMCU'] ?? '');
            if ($matricula === '' || isset($vendorMap[$matricula])) continue;
            if (isset($novosVendors[$matricula])) continue;

            $novosVendors[$matricula] = [
                'matricula'  => $matricula,
                'nome'       => mb_substr(trim($data['FORCA_VENDAS_NOME'] ?? 'Sem Nome'), 0, 200),
                'estado_se'  => mb_substr(trim($data['SE'] ?? ''), 0, 2),
                'lotacao'    => mb_substr(trim($data['GERENCIA_VENDAS'] ?? ''), 0, 100),
                'tipo_acom'  => null,
                'ativo'      => true,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ];
        }
        fclose($fh);

        CLI::write("      Vendors já cadastrados : " . count($vendorMap));
        CLI::write("      Novos vendors a inserir: " . count($novosVendors));

        if (! $dryRun && count($novosVendors) > 0) {
            $chunks = array_chunk(array_values($novosVendors), self::BATCH_SIZE);
            foreach ($chunks as $chunk) {
                $db->table('vendors')->insertBatch($chunk);
            }
            // Recarrega o mapa completo
            $vendorMap = [];
            foreach ($db->query("SELECT id, matricula FROM vendors")->getResultArray() as $row) {
                $vendorMap[trim($row['matricula'])] = (int) $row['id'];
            }
            CLI::write("      ✔ Vendors inseridos com sucesso.", 'green');
        } elseif ($dryRun) {
            CLI::write("      [DRY-RUN] Nenhum vendor foi inserido.", 'dark_gray');
        }
        CLI::write('');

        // ─────────────────────────────────────────────────────────────────────
        // FASE 2: UPSERT de client_wallets + FASE 3: wallet_movements
        // Estratégia: Envia os dados em lotes para uma tabela temporária no
        // Postgres e usa SQL para decidir INSERT vs UPDATE sem alocar memória PHP.
        // ─────────────────────────────────────────────────────────────────────
        CLI::write('[2/3] Importando carteiras (UPSERT)...', 'yellow');

        if (! $dryRun) {
            // Cria tabela temporária de staging para esta sessão
            $db->query("
                CREATE TEMP TABLE IF NOT EXISTS _import_carteira (
                    cnpj               CHAR(14)     NOT NULL,
                    vendor_id          INTEGER,
                    status_operacional VARCHAR(30)  NOT NULL DEFAULT 'novo',
                    origem_atribuicao  VARCHAR(10)  DEFAULT 'manual'
                )
            ");
            $db->query("TRUNCATE TABLE _import_carteira");
        }

        $fh = fopen($csvPath, 'r');
        $header = fgetcsv($fh, 0, ';');
        $header = array_map('trim', $header);

        $stagingBatch   = [];
        $movementBatch  = [];
        $processed      = 0;
        $skippedCnpj    = 0;
        $skippedVendor  = 0;
        $now            = date('Y-m-d H:i:s');

        while (($row = fgetcsv($fh, 0, ';')) !== false) {
            if (count($row) < count($header)) continue;
            // Converte Latin-1 → UTF-8
            $row = array_map(fn($v) => mb_convert_encoding($v, 'UTF-8', 'ISO-8859-1'), $row);
            $data = array_combine($header, $row);
            $processed++;

            // Limpa e valida o CNPJ
            $cnpj = preg_replace('/[^0-9]/', '', trim($data['CNPJ'] ?? ''));
            if (strlen($cnpj) < 8) {
                $skippedCnpj++;
                continue;
            }
            $cnpj = str_pad($cnpj, 14, '0', STR_PAD_LEFT);

            $matricula = trim($data['MATRICULA_MCMCU'] ?? '');
            $vendorId  = $vendorMap[$matricula] ?? null;
            if ($vendorId === null) $skippedVendor++;

            $ciclo      = trim($data['CICLO_DE_VIDA'] ?? '');
            $prospeccao = strtoupper(trim($data['PROSPECCAO'] ?? ''));
            $conta      = trim($data['CONTA_NUMERO'] ?? '');
            $status     = $this->mapStatus($ciclo, $prospeccao, $conta);

            $stagingBatch[] = [
                'cnpj'               => $cnpj,
                'vendor_id'          => $vendorId,
                'status_operacional' => $status,
                'origem_atribuicao'  => 'manual',
            ];

            // Flush do lote de staging
            if (count($stagingBatch) >= self::BATCH_SIZE) {
                if (! $dryRun) {
                    $this->flushStagingBatch($db, $stagingBatch, $now);
                }
                $stagingBatch = [];

                $pct = round(($processed / $totalLines) * 100);
                CLI::write(sprintf(
                    "      Processados: %s / %s (%d%%)...",
                    number_format($processed, 0, ',', '.'),
                    number_format($totalLines, 0, ',', '.'),
                    $pct
                ));
            }
        }
        fclose($fh);

        // Flush final
        if (! $dryRun && ! empty($stagingBatch)) {
            $this->flushStagingBatch($db, $stagingBatch, $now);
        }

        // Aplica o UPSERT em lote via SQL (muito mais eficiente)
        $inserted = 0;
        $updated  = 0;
        if (! $dryRun) {
            CLI::write("      Aplicando UPSERT no banco (INSERT novos / UPDATE existentes)...");

            // Registra movimentações para CNPJs que vão mudar de vendor
            $db->query("
                INSERT INTO wallet_movements (cnpj, vendor_id_anterior, vendor_id_novo, tipo_movimento, motivo, created_at)
                SELECT
                    s.cnpj,
                    cw.vendor_id,
                    s.vendor_id,
                    'automatico',
                    'Carga do relatório geral de carteiras (vendor atualizado)',
                    '{$now}'
                FROM _import_carteira s
                INNER JOIN client_wallets cw ON cw.cnpj = s.cnpj
                WHERE s.vendor_id IS NOT NULL
                  AND (cw.vendor_id IS NULL OR cw.vendor_id <> s.vendor_id)
            ");

            // INSERT de novos CNPJs (ON CONFLICT = ignora duplicatas)
            $result = $db->query("
                INSERT INTO client_wallets (cnpj, vendor_id, status_operacional, origem_atribuicao, atribuido_em, created_at, updated_at)
                SELECT
                    s.cnpj,
                    s.vendor_id,
                    s.status_operacional,
                    'manual',
                    '{$now}',
                    '{$now}',
                    '{$now}'
                FROM _import_carteira s
                WHERE NOT EXISTS (SELECT 1 FROM client_wallets cw WHERE cw.cnpj = s.cnpj)
                ON CONFLICT (cnpj) DO NOTHING
            ");

            // Registra movimentações de carga inicial para os novos
            $db->query("
                INSERT INTO wallet_movements (cnpj, vendor_id_anterior, vendor_id_novo, tipo_movimento, motivo, created_at)
                SELECT
                    cw.cnpj,
                    NULL,
                    cw.vendor_id,
                    'automatico',
                    'Carga inicial: relatório geral de carteiras',
                    '{$now}'
                FROM client_wallets cw
                WHERE cw.created_at = '{$now}'
                  AND cw.vendor_id IS NOT NULL
            ");

            // UPDATE de CNPJs existentes
            $db->query("
                UPDATE client_wallets cw
                SET
                    vendor_id          = s.vendor_id,
                    status_operacional = s.status_operacional,
                    origem_atribuicao  = 'manual',
                    updated_at         = '{$now}'
                FROM _import_carteira s
                WHERE cw.cnpj = s.cnpj
            ");

            // Conta resultados
            $inserted = (int) $db->query("SELECT COUNT(*) AS c FROM client_wallets WHERE created_at = '{$now}'")->getRow()->c;
            $updated  = $processed - $skippedCnpj - $inserted;

            $db->query("DROP TABLE IF EXISTS _import_carteira");
        }

        CLI::write('');
        CLI::write('[3/3] Concluído!', 'yellow');
        CLI::write('');
        CLI::write('╔══════════════════════════════════════════════════════╗', 'green');
        CLI::write('║                   RESUMO FINAL                      ║', 'green');
        CLI::write('╠══════════════════════════════════════════════════════╣', 'green');
        CLI::write(sprintf('║  Total de linhas processadas : %s', str_pad(number_format($processed, 0, ',', '.'), 22)), 'green');
        CLI::write(sprintf('║  Novos CNPJs inseridos       : %s', str_pad(number_format($inserted, 0, ',', '.'), 22)), 'green');
        CLI::write(sprintf('║  CNPJs atualizados           : %s', str_pad(number_format($updated, 0, ',', '.'), 22)), 'green');
        CLI::write(sprintf('║  CNPJs ignorados (inválido)  : %s', str_pad(number_format($skippedCnpj, 0, ',', '.'), 22)), 'green');
        CLI::write(sprintf('║  Sem vendor mapeado          : %s', str_pad(number_format($skippedVendor, 0, ',', '.'), 22)), 'green');
        CLI::write('╚══════════════════════════════════════════════════════╝', 'green');
        CLI::write('');

        if ($dryRun) {
            CLI::write('⚠  DRY-RUN: Nenhum dado foi persistido no banco de dados.', 'yellow');
        } else {
            CLI::write('✔  Importação concluída com sucesso!', 'green');
        }
        CLI::write('');
    }

    /**
     * Mapeia o ciclo de vida e os campos de prospecção do CSV para o status_operacional do SPIV.
     */
    private function mapStatus(string $ciclo, string $prospeccao, string $conta): string
    {
        $cicloLower = mb_strtolower(trim($ciclo));

        // Cliente com contrato ativo = convertido
        if ($conta !== '') {
            return 'convertido';
        }

        return match (true) {
            str_contains($cicloLower, 'fideliz')    => 'em_acompanhamento',
            str_contains($cicloLower, 'prospeccao'),
            str_contains($cicloLower, 'prospeç'),
            str_contains($cicloLower, 'prospec'),
            $prospeccao === 'SIM',
            str_contains($cicloLower, 'prospectar') => 'novo',
            str_contains($cicloLower, 'recupera')   => 'inativo',
            str_contains($cicloLower, 'decl')        => 'inativo',
            str_contains($cicloLower, 'rentabil')    => 'em_acompanhamento',
            default                                   => 'novo',
        };
    }

    /**
     * Envia um lote de registros para a tabela temporária de staging no Postgres.
     */
    private function flushStagingBatch(\CodeIgniter\Database\BaseConnection $db, array $batch, string $now): void
    {
        if (empty($batch)) return;

        $values = [];
        foreach ($batch as $r) {
            $vendorId = $r['vendor_id'] === null ? 'NULL' : (int) $r['vendor_id'];
            $values[] = sprintf(
                "(%s, %s, %s, %s)",
                $db->escape($r['cnpj']),
                $vendorId,
                $db->escape($r['status_operacional']),
                $db->escape($r['origem_atribuicao'])
            );
        }

        $db->query("
            INSERT INTO _import_carteira (cnpj, vendor_id, status_operacional, origem_atribuicao)
            VALUES " . implode(',', $values) . "
            ON CONFLICT DO NOTHING
        ");
    }
}
