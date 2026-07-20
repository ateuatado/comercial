<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * ScoringConfigSeeder
 *
 * Popula os pesos padrão do algoritmo de scoring preditivo.
 * Cada chave representa um fator do score. A soma weight_cnae +
 * weight_capital + weight_email + weight_nome_fantasia + weight_localizacao = 100.
 * O amortization_factor (0-100) é o percentual aplicado aos CNAEs secundários.
 */
class ScoringConfigSeeder extends Seeder
{
    public function run()
    {
        $now = date('Y-m-d H:i:s');

        $configs = [
            ['key' => 'weight_cnae',           'value' => '40', 'label' => 'Peso do Ramo de Atividade (CNAE)',         'created_at' => $now, 'updated_at' => $now],
            ['key' => 'weight_capital',         'value' => '20', 'label' => 'Peso do Porte (Capital Social)',           'created_at' => $now, 'updated_at' => $now],
            ['key' => 'weight_email',           'value' => '15', 'label' => 'Peso da Maturidade Digital (E-mail)',      'created_at' => $now, 'updated_at' => $now],
            ['key' => 'weight_nome_fantasia',   'value' => '10', 'label' => 'Peso da Presença Comercial (Marca)',       'created_at' => $now, 'updated_at' => $now],
            ['key' => 'weight_localizacao',     'value' => '15', 'label' => 'Peso da Localização Estratégica',         'created_at' => $now, 'updated_at' => $now],
            ['key' => 'amortization_factor',    'value' => '70', 'label' => 'Fator de Amortização CNAE Secundário (%)', 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'capital_tier_high',      'value' => '100000', 'label' => 'Capital Social Alto (R$)',            'created_at' => $now, 'updated_at' => $now],
            ['key' => 'capital_tier_mid',       'value' => '20000',  'label' => 'Capital Social Médio (R$)',           'created_at' => $now, 'updated_at' => $now],
        ];

        foreach ($configs as $cfg) {
            $this->db->query(
                "INSERT INTO scoring_config (key, value, label, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?)
                 ON CONFLICT (key) DO NOTHING",
                [$cfg['key'], $cfg['value'], $cfg['label'], $cfg['created_at'], $cfg['updated_at']]
            );
        }

        echo "ScoringConfigSeeder: " . count($configs) . " configurações inseridas.\n";
    }
}
