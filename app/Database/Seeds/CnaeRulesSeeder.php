<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * CnaeRulesSeeder
 *
 * Popula as regras de peso por código CNAE.
 * Foco: Comércio varejista físico transportável = clientes prioritários dos Correios.
 *
 * Pesos:
 *   40 = Interesse máximo (e-commerce físico de bens transportáveis)
 *   30 = Alto interesse (indústria leve com venda direta ou distribuição)
 *   20 = Interesse moderado (serviços com expedição eventual)
 *   10 = Baixo interesse (serviços locais ou intangíveis)
 *    5 = Mínimo (atividades sem expedição física)
 */
class CnaeRulesSeeder extends Seeder
{
    public function run()
    {
        $now   = date('Y-m-d H:i:s');
        $rules = [
            // ──────────────────────────────────────────────────────────────
            // PESO 40 — Comércio Varejista (e-commerce prioritário)
            // ──────────────────────────────────────────────────────────────
            ['4711-3/01', 40, 'Comércio varejista de mercadorias em geral - hipermercados'],
            ['4711-3/02', 40, 'Comércio varejista de mercadorias em geral - supermercados'],
            ['4713-0/01', 40, 'Lojas de departamentos ou magazines'],
            ['4713-0/02', 40, 'Lojas de variedades, exceto lojas de departamentos ou magazines'],
            ['4721-1/01', 35, 'Padaria e confeitaria com predominância de produção própria'],
            ['4731-8/00', 35, 'Comércio varejista de combustíveis para veículos automotores'],
            ['4741-5/00', 40, 'Comércio varejista de tintas e materiais para pintura'],
            ['4742-3/00', 35, 'Comércio varejista de material elétrico'],
            ['4743-1/00', 35, 'Comércio varejista de vidros'],
            ['4744-0/01', 35, 'Comércio varejista de ferragens e ferramentas'],
            ['4744-0/02', 35, 'Comércio varejista de madeira e artefatos de madeira'],
            ['4744-0/03', 35, 'Comércio varejista de materiais hidráulicos'],
            ['4744-0/04', 40, 'Comércio varejista de cal, areia, pedra britada, tijolos'],
            ['4744-0/05', 35, 'Comércio varejista de materiais de construção não especificados'],
            ['4744-0/99', 40, 'Comércio varejista de materiais de construção em geral'],
            ['4751-2/01', 40, 'Comércio varejista especializado de equipamentos de telefonia e comunicação'],
            ['4751-2/02', 40, 'Recarga de cartuchos para equipamentos de informática'],
            ['4752-1/00', 40, 'Comércio varejista especializado de equipamentos de informática e comunicação'],
            ['4753-9/00', 40, 'Comércio varejista especializado de eletrodomésticos e equipamentos de áudio e vídeo'],
            ['4754-7/01', 40, 'Comércio varejista de móveis'],
            ['4754-7/02', 40, 'Comércio varejista de artigos de colchoaria'],
            ['4754-7/03', 35, 'Comércio varejista de artigos de iluminação'],
            ['4755-5/01', 40, 'Comércio varejista de tecidos'],
            ['4755-5/02', 40, 'Comércio varejista de artigos de armarinho'],
            ['4755-5/03', 40, 'Comércio varejista de artigos de cama, mesa e banho'],
            ['4756-3/00', 40, 'Comércio varejista especializado de instrumentos musicais e acessórios'],
            ['4757-1/00', 40, 'Comércio varejista especializado de peças e acessórios para aparelhos eletroeletrônicos'],
            ['4759-8/01', 40, 'Comércio varejista de artigos de tapeçaria, cortinas e persianas'],
            ['4759-8/99', 40, 'Comércio varejista de outros artigos de uso doméstico não especificados'],
            ['4761-0/01', 40, 'Comércio varejista de livros'],
            ['4761-0/02', 35, 'Comércio varejista de jornais e revistas'],
            ['4761-0/03', 40, 'Comércio varejista de artigos de papelaria'],
            ['4762-8/00', 35, 'Comércio varejista de discos, CDs, DVDs e fitas'],
            ['4763-6/01', 35, 'Comércio varejista de brinquedos e artigos recreativos'],
            ['4763-6/02', 40, 'Comércio varejista de artigos esportivos'],
            ['4763-6/03', 40, 'Comércio varejista de bicicletas, triciclos e similares'],
            ['4763-6/04', 40, 'Comércio varejista de artigos de caça, pesca e camping'],
            ['4763-6/05', 40, 'Comércio varejista de embarcações e outros veículos recreativos'],
            ['4771-7/01', 40, 'Comércio varejista de produtos farmacêuticos, sem manipulação de fórmulas'],
            ['4771-7/02', 35, 'Comércio varejista de produtos farmacêuticos, com manipulação de fórmulas'],
            ['4771-7/03', 40, 'Comércio varejista de produtos farmacêuticos homeopáticos'],
            ['4771-7/04', 35, 'Comércio varejista de medicamentos veterinários'],
            ['4772-5/00', 40, 'Comércio varejista de cosméticos, produtos de perfumaria e de higiene pessoal'],
            ['4773-3/00', 35, 'Comércio varejista de artigos médicos e ortopédicos'],
            ['4774-1/00', 40, 'Comércio varejista de artigos de óptica'],
            ['4781-4/00', 40, 'Comércio varejista de artigos do vestuário e acessórios'],
            ['4782-2/01', 40, 'Comércio varejista de calçados'],
            ['4782-2/02', 40, 'Comércio varejista de artigos de viagem'],
            ['4783-1/01', 40, 'Comércio varejista de joias e relógios'],
            ['4783-1/02', 35, 'Comércio varejista de bijuterias e artesanatos'],
            ['4784-9/00', 40, 'Comércio varejista de gás liqüefeito de petróleo (GLP)'],
            ['4785-7/01', 40, 'Comércio varejista de antiguidades'],
            ['4785-7/99', 40, 'Comércio varejista de outros artigos usados'],
            ['4789-0/01', 40, 'Comércio varejista de suvenires, bijuterias e artesanatos'],
            ['4789-0/02', 40, 'Comércio varejista de plantas e flores naturais'],
            ['4789-0/03', 40, 'Comércio varejista de objetos de arte'],
            ['4789-0/04', 40, 'Comércio varejista de animais vivos e de artigos e alimentos para animais de estimação'],
            ['4789-0/05', 30, 'Comércio varejista de produtos saneantes domissanitários'],
            ['4789-0/06', 35, 'Comércio varejista de fogos de artifício e artigos pirotécnicos'],
            ['4789-0/07', 35, 'Comércio varejista de equipamentos para escritório'],
            ['4789-0/08', 40, 'Comércio varejista de artigos fotográficos e para filmagem'],
            ['4789-0/99', 40, 'Comércio varejista de outros produtos não especificados anteriormente'],

            // ──────────────────────────────────────────────────────────────
            // PESO 30 — Comércio Atacadista e Indústria com Venda Direta
            // ──────────────────────────────────────────────────────────────
            ['4649-4/08', 30, 'Comércio atacadista de produtos de higiene, limpeza e conservação domiciliar'],
            ['4649-4/99', 30, 'Comércio atacadista de outros equipamentos e artigos de uso pessoal e doméstico'],
            ['4644-3/01', 30, 'Comércio atacadista de medicamentos e drogas de uso humano'],
            ['4644-3/02', 30, 'Comércio atacadista de instrumentos e materiais para uso médico, cirúrgico, hospitalar'],
            ['4647-8/02', 30, 'Comércio atacadista de roupas e acessórios para uso profissional e de segurança'],
            ['4612-5/00', 30, 'Representantes comerciais e agentes do comércio de combustíveis, minerais, produtos siderúrgicos e químicos'],
            ['4613-3/00', 30, 'Representantes comerciais e agentes do comércio de madeira, material de construção e ferragens'],
            ['4616-8/00', 30, 'Representantes comerciais e agentes do comércio de têxteis, vestuário, calçados e artigos de viagem'],
            ['4617-6/00', 30, 'Representantes comerciais e agentes do comércio de produtos alimentícios, bebidas e fumo'],
            ['4619-2/00', 30, 'Representantes comerciais e agentes do comércio de mercadorias em geral não especificadas'],

            // ──────────────────────────────────────────────────────────────
            // PESO 20 — Serviços com Expedição Eventual
            // ──────────────────────────────────────────────────────────────
            ['5811-5/00', 20, 'Edição de livros'],
            ['5812-3/00', 20, 'Edição de jornais'],
            ['5813-1/00', 20, 'Edição de revistas'],
            ['5819-1/00', 20, 'Edição de cadastros, listas e outros produtos gráficos'],
            ['1811-3/01', 20, 'Impressão de jornais'],
            ['1811-3/02', 20, 'Impressão de livros, revistas e outras publicações periódicas'],
            ['1813-0/99', 20, 'Impressão de material para outros usos'],

            // ──────────────────────────────────────────────────────────────
            // PESO 10 — Serviços Locais e Intangíveis
            // ──────────────────────────────────────────────────────────────
            ['6201-5/00', 10, 'Desenvolvimento de programas de computador sob encomenda'],
            ['6202-3/00', 10, 'Desenvolvimento e licenciamento de programas de computador customizáveis'],
            ['6203-1/00', 10, 'Desenvolvimento e licenciamento de programas de computador não-customizáveis'],
            ['6911-7/01', 10, 'Serviços advocatícios'],
            ['6920-6/01', 10, 'Atividades de contabilidade'],
            ['7020-4/00', 10, 'Atividades de consultoria em gestão empresarial'],
            ['8599-6/04', 10, 'Treinamento em desenvolvimento profissional e gerencial'],
        ];

        $inserted = 0;
        foreach ($rules as [$code, $weight, $desc]) {
            $this->db->query(
                "INSERT INTO cnae_scoring_rules (cnae_code, weight, description, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?)
                 ON CONFLICT (cnae_code) DO NOTHING",
                [$code, $weight, $desc, $now, $now]
            );
            $inserted++;
        }

        echo "CnaeRulesSeeder: {$inserted} regras de CNAE inseridas.\n";
    }
}
