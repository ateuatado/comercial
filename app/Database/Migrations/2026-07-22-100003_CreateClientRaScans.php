<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Cache de resultados do scanner Reclame Aqui por CNPJ.
 *
 * Um registro por CNPJ. Cada novo scan sobrescreve o anterior (UPSERT).
 * Campos:
 *   - resultado_json : array de itens orgânicos retornados pela Serper (ou [])
 *   - total          : quantidade de ocorrências encontradas (0 = limpo)
 *   - empresa_nome   : nome que foi usado na busca (log + debug)
 *   - pesquisado_por : user_id de quem fez a consulta
 *   - pesquisado_em  : timestamp da última pesquisa
 */
class CreateClientRaScans extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'cnpj' => [
                'type'       => 'VARCHAR',
                'constraint' => 14,
            ],
            'empresa_nome' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'status' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'default'    => 'nao_encontrado',
                'comment'    => 'encontrado | nao_encontrado | erro',
            ],
            'total' => [
                'type'    => 'INTEGER',
                'default' => 0,
            ],
            'resultado_json' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'pesquisado_por' => [
                'type' => 'INTEGER',
                'null' => true,
                'comment' => 'user_id (Shield) de quem executou o scan',
            ],
            'pesquisado_em' => [
                'type' => 'TIMESTAMP',
                'null' => true,
            ],
        ]);

        $this->forge->addPrimaryKey('cnpj');
        $this->forge->createTable('client_ra_scans');
    }

    public function down(): void
    {
        $this->forge->dropTable('client_ra_scans', true);
    }
}
