<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use CodeIgniter\CLI\CLI;

/**
 * Controller: ImportController
 * Upload e importação de CSV de carteiras via interface web.
 */
class ImportController extends BaseController
{
    /**
     * Mapeamento CSV → DB (mesmo do ImportCarteiraFull command)
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

    private const BATCH_SIZE = 1000;

    /**
     * Tela de upload + histórico de importações.
     */
    public function index(): string
    {
        $db = db_connect();

        // Histórico de importações
        $logs = $db->query("SELECT * FROM import_logs ORDER BY created_at DESC LIMIT 20")->getResultArray();

        // Stats da base atual
        $totalCarteira = (int) ($db->query("SELECT COUNT(*) AS c FROM carteira_raw")->getRow()->c ?? 0);
        $totalVendedores = (int) ($db->query("SELECT COUNT(*) AS c FROM vendor_users WHERE ativo = true")->getRow()->c ?? 0);

        return view('admin/import/index', [
            'logs'             => $logs,
            'totalCarteira'    => $totalCarteira,
            'totalVendedores'  => $totalVendedores,
        ]);
    }

    /**
     * Recebe upload do CSV e mostra preview.
     */
    public function upload()
    {
        $file = $this->request->getFile('csv_file');

        if (!$file || !$file->isValid()) {
            return redirect()->to('/admin/importar')->with('error', 'Arquivo inválido ou não enviado.');
        }

        // Validar extensão
        $ext = strtolower($file->getExtension());
        if (!in_array($ext, ['csv', 'txt'])) {
            return redirect()->to('/admin/importar')->with('error', 'Somente arquivos CSV são aceitos.');
        }

        // Mover para pasta temporária
        $newName = 'import_' . date('Ymd_His') . '.' . $ext;
        $file->move(WRITEPATH . 'uploads', $newName);
        $filePath = WRITEPATH . 'uploads/' . $newName;

        // Ler preview (5 primeiras linhas)
        $fh = fopen($filePath, 'r');
        $headerRaw = fgetcsv($fh, 0, ';');
        $header = array_map(fn($v) => mb_convert_encoding(trim($v), 'UTF-8', 'ISO-8859-1'), $headerRaw);

        // Validar colunas esperadas
        $missingCols = [];
        foreach (array_keys(self::CSV_TO_DB) as $col) {
            if (!in_array($col, $header)) {
                $missingCols[] = $col;
            }
        }

        $previewRows = [];
        for ($i = 0; $i < 5; $i++) {
            $row = fgetcsv($fh, 0, ';');
            if ($row === false) break;
            $row = array_map(fn($v) => mb_convert_encoding(trim($v), 'UTF-8', 'ISO-8859-1'), $row);
            $previewRows[] = $row;
        }
        fclose($fh);

        // Contar total de linhas
        $totalLines = 0;
        $fc = fopen($filePath, 'r');
        fgets($fc); // pular header
        while (fgets($fc) !== false) $totalLines++;
        fclose($fc);

        return view('admin/import/preview', [
            'filename'    => $newName,
            'header'      => $header,
            'previewRows' => $previewRows,
            'totalLines'  => $totalLines,
            'missingCols' => $missingCols,
            'fileSize'    => filesize($filePath),
        ]);
    }

    /**
     * Confirma e executa a importação.
     */
    public function confirm()
    {
        $filename = $this->request->getPost('filename');
        $filePath = WRITEPATH . 'uploads/' . $filename;

        if (!$filename || !file_exists($filePath)) {
            return redirect()->to('/admin/importar')->with('error', 'Arquivo não encontrado. Faça upload novamente.');
        }

        $db = db_connect();
        $user = auth()->user();
        $userName = $user ? $user->username : 'sistema';

        // Criar log
        $db->table('import_logs')->insert([
            'filename'    => $filename,
            'imported_by' => $userName,
            'status'      => 'processando',
            'created_at'  => date('Y-m-d H:i:s'),
        ]);
        $logId = $db->insertID();

        try {
            // Abrir CSV
            $fh = fopen($filePath, 'r');
            $headerRaw = fgetcsv($fh, 0, ';');
            $header = array_map(fn($v) => mb_convert_encoding(trim($v), 'UTF-8', 'ISO-8859-1'), $headerRaw);

            // Mapear índices
            $csvMap = self::CSV_TO_DB;
            $colIndexes = [];
            foreach ($csvMap as $csvCol => $dbCol) {
                $idx = array_search($csvCol, $header);
                if ($idx === false) {
                    throw new \RuntimeException("Coluna esperada não encontrada: {$csvCol}");
                }
                $colIndexes[$csvCol] = $idx;
            }

            // Truncar tabela (importação destrutiva — foto completa)
            $db->query('TRUNCATE TABLE carteira_raw RESTART IDENTITY');

            $batch     = [];
            $processed = 0;
            $skipped   = 0;
            $inserted  = 0;
            $now       = date('Y-m-d H:i:s');

            while (($row = fgetcsv($fh, 0, ';')) !== false) {
                if (count($row) < count($header)) {
                    $skipped++;
                    continue;
                }

                $row = array_map(fn($v) => mb_convert_encoding(trim($v), 'UTF-8', 'ISO-8859-1'), $row);
                $processed++;

                $record = ['created_at' => $now];
                foreach ($csvMap as $csvCol => $dbCol) {
                    $val = $row[$colIndexes[$csvCol]] ?? '';
                    $record[$dbCol] = ($val === '' && $dbCol !== 'cnpj') ? null : $val;
                }

                // Validar CNPJ
                $cnpj = preg_replace('/[^0-9]/', '', $record['cnpj']);
                if (strlen($cnpj) < 8) {
                    $skipped++;
                    continue;
                }
                $record['cnpj'] = str_pad($cnpj, 14, '0', STR_PAD_LEFT);

                $batch[] = $record;

                if (count($batch) >= self::BATCH_SIZE) {
                    $db->table('carteira_raw')->insertBatch($batch);
                    $inserted += count($batch);
                    $batch = [];
                }
            }
            fclose($fh);

            // Flush final
            if (!empty($batch)) {
                $db->table('carteira_raw')->insertBatch($batch);
                $inserted += count($batch);
            }

            // Atualizar log
            $db->table('import_logs')->update([
                'total_lines' => $processed + $skipped,
                'inserted'    => $inserted,
                'skipped'     => $skipped,
                'status'      => 'concluido',
                'updated_at'  => date('Y-m-d H:i:s'),
            ], ['id' => $logId]);

            // Limpar arquivo temporário
            @unlink($filePath);

            return redirect()->to('/admin/importar')->with('success',
                "Importação concluída! {$inserted} registros inseridos, {$skipped} ignorados.");

        } catch (\Throwable $e) {
            $db->table('import_logs')->update([
                'status'        => 'erro',
                'error_message' => $e->getMessage(),
                'updated_at'    => date('Y-m-d H:i:s'),
            ], ['id' => $logId]);

            @unlink($filePath);

            return redirect()->to('/admin/importar')->with('error', 'Erro na importação: ' . $e->getMessage());
        }
    }
}
