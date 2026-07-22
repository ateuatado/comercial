<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class FixOrphanAttachments extends BaseCommand
{
    protected $group       = 'Dev';
    protected $name        = 'fix:orphan-attachments';
    protected $description = 'Remove captacao_attachments com captacao_id = 0 (bug insertID PostgreSQL)';

    public function run(array $params)
    {
        $db   = db_connect();
        $rows = $db->table('captacao_attachments')->where('captacao_id', 0)->get()->getResultArray();

        if (empty($rows)) {
            CLI::write('Nenhum anexo orphan encontrado.', 'green');
            return;
        }

        CLI::write(count($rows) . ' anexo(s) com captacao_id=0:', 'yellow');
        foreach ($rows as $r) {
            CLI::write("  id={$r['id']} | file={$r['original_name']}");
        }

        $db->table('captacao_attachments')->where('captacao_id', 0)->delete();
        CLI::write('Registros removidos do banco.', 'green');
        CLI::write('Arquivos físicos em writable/uploads/captacoes/0/ (se existir) podem ser removidos manualmente.', 'cyan');
    }
}
