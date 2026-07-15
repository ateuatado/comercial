<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class ReportCarteiraRaw extends BaseCommand
{
    protected $group       = 'Carteira';
    protected $name        = 'carteira:report';
    protected $description = 'Gera relatório de categorias da tabela carteira_raw.';

    public function run(array $params): void
    {
        $db = db_connect();
        $total = (int) $db->query("SELECT COUNT(*) AS c FROM carteira_raw")->getRow()->c;

        CLI::write('');
        CLI::write('╔═══════════════════════════════════════════════════════════╗', 'cyan');
        CLI::write('║   RELATÓRIO — carteira_raw (' . number_format($total, 0, ',', '.') . ' registros)             ║', 'cyan');
        CLI::write('╚═══════════════════════════════════════════════════════════╝', 'cyan');
        CLI::write('');

        $columns = [
            'se'               => 'Superintendência (SE)',
            'categoria'        => 'Categoria Institucional',
            'segmento_cliente' => 'Segmento de Cliente',
            'segmento_mercado' => 'Segmento de Mercado',
            'canais_vendas'    => 'Canais de Vendas',
            'prospeccao'       => 'Prospecção',
            'ciclo_de_vida'    => 'Ciclo de Vida',
            'gerencia'         => 'Gerência',
        ];

        foreach ($columns as $col => $label) {
            $rows = $db->query("
                SELECT COALESCE(NULLIF({$col}, ''), '(vazio)') AS val, COUNT(*) AS total
                FROM carteira_raw
                GROUP BY val
                ORDER BY total DESC
            ")->getResultArray();

            CLI::write("── {$label} ({$col}) — " . count($rows) . " valores ──", 'yellow');
            foreach ($rows as $r) {
                $pct = round(($r['total'] / $total) * 100, 1);
                CLI::write(sprintf(
                    "   %-60s %10s  (%s%%)",
                    mb_substr($r['val'], 0, 60),
                    number_format($r['total'], 0, ',', '.'),
                    $pct
                ));
            }
            CLI::write('');
        }

        // Natureza Jurídica (muitos valores, top 15)
        $nats = $db->query("
            SELECT COALESCE(NULLIF(nat_juridica, ''), '(vazio)') AS val, COUNT(*) AS total
            FROM carteira_raw
            GROUP BY val
            ORDER BY total DESC
            LIMIT 15
        ")->getResultArray();
        $natTotal = (int) $db->query("SELECT COUNT(DISTINCT nat_juridica) AS c FROM carteira_raw")->getRow()->c;
        CLI::write("── Natureza Jurídica (nat_juridica) — Top 15 de {$natTotal} valores ──", 'yellow');
        foreach ($nats as $r) {
            $pct = round(($r['total'] / $total) * 100, 1);
            CLI::write(sprintf("   %-60s %10s  (%s%%)", $r['val'], number_format($r['total'], 0, ',', '.'), $pct));
        }
        CLI::write('');

        // Carteiras
        $carteiras = $db->query("
            SELECT COUNT(DISTINCT matricula_mcmcu) AS carteiras,
                   COUNT(DISTINCT forca_vendas_nome) AS nomes
            FROM carteira_raw
        ")->getRow();
        CLI::write("── Carteiras ──", 'yellow');
        CLI::write("   Matrículas/MCMCU distintas  : {$carteiras->carteiras}");
        CLI::write("   Nomes de carteira distintos : {$carteiras->nomes}");
        CLI::write('');

        // Top 20 carteiras por volume
        $top = $db->query("
            SELECT forca_vendas_nome, matricula_mcmcu, COUNT(*) AS total
            FROM carteira_raw
            GROUP BY forca_vendas_nome, matricula_mcmcu
            ORDER BY total DESC
            LIMIT 20
        ")->getResultArray();
        CLI::write("── Top 20 Carteiras (por volume de clientes) ──", 'yellow');
        foreach ($top as $r) {
            CLI::write(sprintf(
                "   %-45s %-12s %10s",
                mb_substr($r['forca_vendas_nome'], 0, 45),
                $r['matricula_mcmcu'],
                number_format($r['total'], 0, ',', '.')
            ));
        }
        CLI::write('');

        // Top 20 CNAEs
        $cnaes = $db->query("
            SELECT cnae, cnae_desc, COUNT(*) AS total
            FROM carteira_raw
            WHERE cnae IS NOT NULL AND cnae != ''
            GROUP BY cnae, cnae_desc
            ORDER BY total DESC
            LIMIT 20
        ")->getResultArray();
        CLI::write("── Top 20 CNAEs ──", 'yellow');
        foreach ($cnaes as $r) {
            CLI::write(sprintf(
                "   %-12s %-50s %10s",
                $r['cnae'],
                mb_substr($r['cnae_desc'], 0, 50),
                number_format($r['total'], 0, ',', '.')
            ));
        }
        CLI::write('');

        // Hierarquia de gestão
        $coods = $db->query("
            SELECT COUNT(DISTINCT mtr_cood) AS coordenadores,
                   COUNT(DISTINCT gerencia_vendas) AS gerencias_vendas
            FROM carteira_raw
            WHERE mtr_cood IS NOT NULL
        ")->getRow();
        CLI::write("── Hierarquia de Gestão ──", 'yellow');
        CLI::write("   Coordenadores distintos (mtr_cood)  : {$coods->coordenadores}");
        CLI::write("   Gerências de vendas distintas        : {$coods->gerencias_vendas}");
        CLI::write('');

        // Emails
        $emails = $db->query("
            SELECT COUNT(DISTINCT forca_vendas_email) AS emails
            FROM carteira_raw
            WHERE forca_vendas_email IS NOT NULL
        ")->getRow();
        CLI::write("── Contatos ──", 'yellow');
        CLI::write("   Emails de força de vendas distintos : {$emails->emails}");
        CLI::write('');
    }
}
