<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Seeder: SegmentServicesSeeder
 * Popula segment_services com serviços dos Correios por segmento de mercado.
 */
class SegmentServicesSeeder extends Seeder
{
    public function run(): void
    {
        $db = $this->db;

        // Serviços organizados por segmento (top 10 da base + GERAL)
        $data = [
            'GERAL' => [
                ['servico_nome' => 'SEDEX',            'icone' => '📦', 'cor' => '#e74c3c', 'servico_descricao' => 'Entrega expressa nacional'],
                ['servico_nome' => 'PAC',              'icone' => '📬', 'cor' => '#3498db', 'servico_descricao' => 'Entrega econômica nacional'],
                ['servico_nome' => 'SEDEX 12',         'icone' => '⏰', 'cor' => '#e67e22', 'servico_descricao' => 'Entrega até 12h do dia seguinte'],
                ['servico_nome' => 'SEDEX 10',         'icone' => '🕙', 'cor' => '#f39c12', 'servico_descricao' => 'Entrega até 10h do dia seguinte'],
                ['servico_nome' => 'SEDEX Hoje',       'icone' => '⚡', 'cor' => '#9b59b6', 'servico_descricao' => 'Entrega no mesmo dia'],
                ['servico_nome' => 'Carta Registrada',  'icone' => '✉️', 'cor' => '#1abc9c', 'servico_descricao' => 'Correspondência com rastreamento'],
                ['servico_nome' => 'Telegrama',         'icone' => '📨', 'cor' => '#34495e', 'servico_descricao' => 'Mensagem urgente com valor legal'],
            ],
            'VESTUARIO - MATERIAL ESPORTIVO - CALCADOS' => [
                ['servico_nome' => 'SEDEX Contrato',   'icone' => '🤝', 'cor' => '#e74c3c', 'servico_descricao' => 'Contrato corporativo SEDEX'],
                ['servico_nome' => 'Logística Reversa', 'icone' => '🔄', 'cor' => '#f39c12', 'servico_descricao' => 'Troca e devolução de peças'],
                ['servico_nome' => 'Embalagens',       'icone' => '📦', 'cor' => '#8e44ad', 'servico_descricao' => 'Embalagens padronizadas dos Correios'],
                ['servico_nome' => 'Coleta Programada', 'icone' => '🚚', 'cor' => '#27ae60', 'servico_descricao' => 'Coleta em horário agendado'],
                ['servico_nome' => 'Mini Envios',      'icone' => '📎', 'cor' => '#2ecc71', 'servico_descricao' => 'Itens até 300g com economia'],
            ],
            'ALIMENTOS - BEBIDAS' => [
                ['servico_nome' => 'PAC Contrato',     'icone' => '📋', 'cor' => '#3498db', 'servico_descricao' => 'Entrega econômica com contrato'],
                ['servico_nome' => 'SEDEX Contrato',   'icone' => '🤝', 'cor' => '#e74c3c', 'servico_descricao' => 'Entrega expressa com contrato'],
                ['servico_nome' => 'Coleta Programada', 'icone' => '🚚', 'cor' => '#27ae60', 'servico_descricao' => 'Coleta recorrente no estabelecimento'],
                ['servico_nome' => 'Embalagem Especial','icone' => '🧊', 'cor' => '#2980b9', 'servico_descricao' => 'Embalagens para itens sensíveis'],
            ],
            'ELETROELETRONICOS - ELETRODOMESTICOS' => [
                ['servico_nome' => 'SEDEX Contrato',   'icone' => '🤝', 'cor' => '#e74c3c', 'servico_descricao' => 'Entrega expressa segura'],
                ['servico_nome' => 'Declaração de Valor','icone'=> '💰', 'cor' => '#f1c40f', 'servico_descricao' => 'Seguro adicional para itens de valor'],
                ['servico_nome' => 'Logística Reversa', 'icone' => '🔄', 'cor' => '#f39c12', 'servico_descricao' => 'Assistência técnica e devoluções'],
                ['servico_nome' => 'Coleta Programada', 'icone' => '🚚', 'cor' => '#27ae60', 'servico_descricao' => 'Coleta agendada recorrente'],
            ],
            'COSMETICOS - PERFUMARIA' => [
                ['servico_nome' => 'SEDEX Contrato',   'icone' => '🤝', 'cor' => '#e74c3c', 'servico_descricao' => 'Entrega expressa com contrato'],
                ['servico_nome' => 'Mini Envios',      'icone' => '📎', 'cor' => '#2ecc71', 'servico_descricao' => 'Amostras e itens pequenos'],
                ['servico_nome' => 'Mala Direta',      'icone' => '📮', 'cor' => '#3498db', 'servico_descricao' => 'Promoções e catálogos em massa'],
                ['servico_nome' => 'Fulfillment',      'icone' => '🏭', 'cor' => '#9b59b6', 'servico_descricao' => 'Logística completa'],
            ],
            'VEICULOS - PECAS - ACESSORIOS' => [
                ['servico_nome' => 'SEDEX Contrato',   'icone' => '🤝', 'cor' => '#e74c3c', 'servico_descricao' => 'Peças urgentes com rastreio'],
                ['servico_nome' => 'PAC Grande',       'icone' => '📦', 'cor' => '#3498db', 'servico_descricao' => 'Peças pesadas com economia'],
                ['servico_nome' => 'Declaração de Valor','icone'=> '💰', 'cor' => '#f1c40f', 'servico_descricao' => 'Seguro para peças de valor'],
                ['servico_nome' => 'Logística Reversa', 'icone' => '🔄', 'cor' => '#f39c12', 'servico_descricao' => 'Devolução de garantia'],
            ],
            'GOVERNO' => [
                ['servico_nome' => 'Carta Registrada', 'icone' => '✉️', 'cor' => '#1abc9c', 'servico_descricao' => 'Comunicações oficiais'],
                ['servico_nome' => 'AR Digital',       'icone' => '📝', 'cor' => '#e67e22', 'servico_descricao' => 'Comprovante de entrega legal digital'],
                ['servico_nome' => 'Mala Direta',      'icone' => '📮', 'cor' => '#3498db', 'servico_descricao' => 'Distribuição em massa de documentos'],
                ['servico_nome' => 'SEDEX Contrato',   'icone' => '🤝', 'cor' => '#e74c3c', 'servico_descricao' => 'Urgências governamentais'],
                ['servico_nome' => 'Telegrama',         'icone' => '📨', 'cor' => '#34495e', 'servico_descricao' => 'Notificações com valor jurídico'],
            ],
            'EDUCACAO' => [
                ['servico_nome' => 'Carta Registrada', 'icone' => '✉️', 'cor' => '#1abc9c', 'servico_descricao' => 'Correspondência acadêmica'],
                ['servico_nome' => 'PAC Contrato',     'icone' => '📋', 'cor' => '#3498db', 'servico_descricao' => 'Envio de material didático'],
                ['servico_nome' => 'Mala Direta',      'icone' => '📮', 'cor' => '#27ae60', 'servico_descricao' => 'Distribuição de material promocional'],
                ['servico_nome' => 'SEDEX Contrato',   'icone' => '🤝', 'cor' => '#e74c3c', 'servico_descricao' => 'Documentos urgentes'],
            ],
            'BANCOS - SEGURADORAS' => [
                ['servico_nome' => 'Carta Registrada', 'icone' => '✉️', 'cor' => '#1abc9c', 'servico_descricao' => 'Correspondência bancária segura'],
                ['servico_nome' => 'AR Digital',       'icone' => '📝', 'cor' => '#e67e22', 'servico_descricao' => 'Comprovante de entrega legal'],
                ['servico_nome' => 'Mala Direta',      'icone' => '📮', 'cor' => '#3498db', 'servico_descricao' => 'Extratos e comunicados em massa'],
                ['servico_nome' => 'SEDEX Contrato',   'icone' => '🤝', 'cor' => '#e74c3c', 'servico_descricao' => 'Documentos e cartões urgentes'],
                ['servico_nome' => 'Malote',           'icone' => '💼', 'cor' => '#8e44ad', 'servico_descricao' => 'Transporte de documentos inter-agências'],
            ],
            'MEDICAMENTOS - SAUDE' => [
                ['servico_nome' => 'SEDEX Contrato',   'icone' => '🤝', 'cor' => '#e74c3c', 'servico_descricao' => 'Entrega rápida de medicamentos'],
                ['servico_nome' => 'PAC Contrato',     'icone' => '📋', 'cor' => '#3498db', 'servico_descricao' => 'Insumos médicos com economia'],
                ['servico_nome' => 'Coleta Programada', 'icone' => '🚚', 'cor' => '#27ae60', 'servico_descricao' => 'Coleta recorrente em laboratórios'],
                ['servico_nome' => 'Declaração de Valor','icone'=> '💰', 'cor' => '#f1c40f', 'servico_descricao' => 'Seguro para itens de alto valor'],
            ],
            'MATERIAL DE CONSTRUCAO' => [
                ['servico_nome' => 'PAC Grande',       'icone' => '📦', 'cor' => '#3498db', 'servico_descricao' => 'Itens pesados com economia'],
                ['servico_nome' => 'SEDEX Contrato',   'icone' => '🤝', 'cor' => '#e74c3c', 'servico_descricao' => 'Peças e componentes urgentes'],
                ['servico_nome' => 'Coleta Programada', 'icone' => '🚚', 'cor' => '#27ae60', 'servico_descricao' => 'Coleta agendada'],
                ['servico_nome' => 'Declaração de Valor','icone'=> '💰', 'cor' => '#f1c40f', 'servico_descricao' => 'Seguro para materiais de valor'],
            ],
        ];

        $inserted = 0;
        $skipped  = 0;
        $ordem    = 0;

        foreach ($data as $segmento => $servicos) {
            $ordem = 0;
            foreach ($servicos as $s) {
                $ordem++;
                $exists = $db->query(
                    "SELECT id FROM segment_services WHERE segmento_mercado = ? AND servico_nome = ? LIMIT 1",
                    [$segmento, $s['servico_nome']]
                )->getRow();

                if ($exists) { $skipped++; continue; }

                $db->query(
                    "INSERT INTO segment_services (segmento_mercado, servico_nome, servico_descricao, icone, cor, ordem, ativo) VALUES (?, ?, ?, ?, ?, ?, true)",
                    [$segmento, $s['servico_nome'], $s['servico_descricao'], $s['icone'], $s['cor'], $ordem]
                );
                $inserted++;
            }
        }

        echo "SegmentServicesSeeder: {$inserted} inseridos, {$skipped} já existentes." . PHP_EOL;
    }
}
