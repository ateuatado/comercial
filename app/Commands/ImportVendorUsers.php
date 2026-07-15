<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Models\VendorUserModel;
use App\Models\CarteiraRawModel;

class ImportVendorUsers extends BaseCommand
{
    protected $group       = 'SPIV';
    protected $name        = 'vendedores:importar';
    protected $description = 'Importa vendedores da carteira_raw para vendor_users.';
    protected $usage       = 'vendedores:importar [--force]';
    protected $arguments   = [];
    protected $options     = [
        '--force' => 'Atualiza registros existentes.',
    ];

    // Prefixos de matrículas fictícias (carteiras coletivas, não são pessoas)
    private const PREFIXOS_FICTICIOS = ['8888', '8002'];

    public function run(array $params)
    {
        $force = CLI::getOption('force') !== null;
        $db    = db_connect();

        CLI::write('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━', 'yellow');
        CLI::write('  SPIV — Importação de Vendedores', 'yellow');
        CLI::write('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━', 'yellow');
        CLI::newLine();

        // 1. Buscar matrículas distintas da carteira_raw
        CLI::write('Consultando matrículas distintas na carteira_raw...', 'light_gray');

        $sql = "
            SELECT
                matricula_mcmcu AS matricula,
                MAX(forca_vendas_nome) AS nome,
                MAX(forca_vendas_email) AS email,
                MAX(se) AS se,
                MAX(gerencia) AS gerencia,
                MAX(mtr_cood) AS mtr_coordenador,
                MAX(nome_cood) AS nome_coordenador,
                MAX(gerencia_vendas) AS gerencia_vendas
            FROM carteira_raw
            WHERE matricula_mcmcu IS NOT NULL
              AND matricula_mcmcu != ''
            GROUP BY matricula_mcmcu
            ORDER BY matricula_mcmcu
        ";

        $registros = $db->query($sql)->getResultArray();
        $total     = count($registros);

        CLI::write("  Encontradas {$total} matrículas distintas.", 'green');
        CLI::newLine();

        // 2. Filtrar matrículas fictícias
        $ficticias = 0;
        $filtrados = [];

        foreach ($registros as $reg) {
            $matricula = trim($reg['matricula']);

            if (empty($matricula)) {
                continue;
            }

            $isFicticia = false;
            foreach (self::PREFIXOS_FICTICIOS as $prefixo) {
                if (str_starts_with($matricula, $prefixo)) {
                    $isFicticia = true;
                    $ficticias++;
                    break;
                }
            }

            if (!$isFicticia) {
                $filtrados[] = $reg;
            }
        }

        CLI::write("  Matrículas fictícias filtradas (8888*/8002*): {$ficticias}", 'light_gray');
        CLI::write("  Matrículas válidas para importação: " . count($filtrados), 'green');
        CLI::newLine();

        // 3. Importar
        $vendorModel = new VendorUserModel();
        $inseridos   = 0;
        $atualizados = 0;
        $ignorados   = 0;
        $erros       = 0;
        $batchSize   = 500;
        $processed   = 0;

        CLI::write('Importando vendedores...', 'light_gray');

        foreach ($filtrados as $reg) {
            $matricula = trim($reg['matricula']);
            $nome      = trim($reg['nome'] ?? '');
            $email     = trim($reg['email'] ?? '');

            // Inferir perfil_vendedor a partir do nome
            $perfil = $this->inferirPerfil($nome);

            $data = [
                'matricula'        => $matricula,
                'nome'             => $nome ?: 'Vendedor ' . $matricula,
                'email'            => !empty($email) ? $email : null,
                'perfil_vendedor'  => $perfil,
                'se'               => trim($reg['se'] ?? '') ?: null,
                'gerencia'         => trim($reg['gerencia'] ?? '') ?: null,
                'mtr_coordenador'  => trim($reg['mtr_coordenador'] ?? '') ?: null,
                'nome_coordenador' => trim($reg['nome_coordenador'] ?? '') ?: null,
                'gerencia_vendas'  => trim($reg['gerencia_vendas'] ?? '') ?: null,
                'ativo'            => true,
            ];

            $existing = $vendorModel->findByMatricula($matricula);

            if ($existing) {
                if ($force) {
                    $vendorModel->update($existing['id'], $data);
                    $atualizados++;
                } else {
                    $ignorados++;
                }
            } else {
                try {
                    $vendorModel->insert($data);
                    $inseridos++;
                } catch (\Exception $e) {
                    $erros++;
                    if ($erros <= 5) {
                        CLI::write("  Erro na matrícula {$matricula}: " . $e->getMessage(), 'red');
                    }
                }
            }

            $processed++;

            // Progress bar a cada batch
            if ($processed % $batchSize === 0 || $processed === count($filtrados)) {
                $pct = round(($processed / count($filtrados)) * 100);
                CLI::showProgress($processed, count($filtrados));
            }
        }

        CLI::showProgress(false);
        CLI::newLine();

        // 4. Resumo
        CLI::write('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━', 'yellow');
        CLI::write('  Resultado da Importação', 'yellow');
        CLI::write('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━', 'yellow');
        CLI::write("  Inseridos:   {$inseridos}", 'green');
        CLI::write("  Atualizados: {$atualizados}", 'light_gray');
        CLI::write("  Ignorados:   {$ignorados} (já existiam)", 'light_gray');
        CLI::write("  Erros:       {$erros}", $erros > 0 ? 'red' : 'light_gray');
        CLI::write("  Fictícias:   {$ficticias} (excluídas)", 'light_gray');
        CLI::newLine();

        // 5. Estatísticas
        $totalVU     = $vendorModel->countAll();
        $coordCount  = $db->query("SELECT COUNT(DISTINCT mtr_coordenador) AS total FROM vendor_users WHERE mtr_coordenador IS NOT NULL")->getRow()->total;
        $seCount     = $db->query("SELECT COUNT(DISTINCT se) AS total FROM vendor_users WHERE se IS NOT NULL AND se != ''")->getRow()->total;
        $perfilCount = $db->query("SELECT perfil_vendedor, COUNT(*) AS total FROM vendor_users GROUP BY perfil_vendedor ORDER BY total DESC")->getResultArray();

        CLI::write('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━', 'cyan');
        CLI::write('  Estatísticas da vendor_users', 'cyan');
        CLI::write('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━', 'cyan');
        CLI::write("  Total de vendedores:  {$totalVU}", 'white');
        CLI::write("  Coordenadores:        {$coordCount}", 'white');
        CLI::write("  Superintendências:    {$seCount}", 'white');
        CLI::newLine();

        CLI::write('  Perfis:', 'white');
        foreach ($perfilCount as $p) {
            $perfName = $p['perfil_vendedor'] ?: '(não identificado)';
            CLI::write("    {$perfName}: {$p['total']}", 'light_gray');
        }

        CLI::newLine();
        CLI::write('Importação concluída!', 'green');
    }

    /**
     * Infere o perfil do vendedor a partir do nome da força de vendas.
     */
    private function inferirPerfil(string $nome): ?string
    {
        $nomeUpper = mb_strtoupper($nome);

        // Ordem importa: testar padrões mais específicos primeiro
        if (str_contains($nomeUpper, 'GERENTE DE CONTA') || str_contains($nomeUpper, 'GC ')) {
            return 'GC';
        }

        if (str_contains($nomeUpper, 'ACOM III') || str_contains($nomeUpper, 'ACOM-III')) {
            return 'ACOM III';
        }

        if (str_contains($nomeUpper, 'ACOM II') || str_contains($nomeUpper, 'ACOM-II')) {
            return 'ACOM II';
        }

        if (str_contains($nomeUpper, 'ACOM I') || str_contains($nomeUpper, 'ACOM-I') || str_contains($nomeUpper, 'ACOM ')) {
            return 'ACOM I';
        }

        if (str_contains($nomeUpper, 'CEM ') || str_contains($nomeUpper, 'CENTRO DE ENTREGA')) {
            return 'CEM';
        }

        if (str_contains($nomeUpper, 'AGF ') || str_contains($nomeUpper, 'AGÊNCIA DE CORREIOS')) {
            return 'AGF';
        }

        if (str_contains($nomeUpper, 'CAC ') || str_contains($nomeUpper, 'CENTRAL DE ATENDIMENTO')) {
            return 'CAC';
        }

        if (str_contains($nomeUpper, 'AC ') || str_contains($nomeUpper, 'AGENCIA')) {
            return 'AC';
        }

        if (str_contains($nomeUpper, 'GEVEN') || str_contains($nomeUpper, 'GERÊNCIA DE VENDAS')) {
            return 'GEVEN';
        }

        if (str_contains($nomeUpper, 'COORD') || str_contains($nomeUpper, 'SUPERVISOR')) {
            return 'COORD';
        }

        return null;
    }
}
