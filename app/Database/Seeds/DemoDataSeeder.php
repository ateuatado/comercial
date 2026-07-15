<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use Faker\Factory;

class DemoDataSeeder extends Seeder
{
    public function run()
    {
        // Instancia o Faker configurado para português do Brasil
        $faker = Factory::create('pt_BR');

        // Para evitar erros de chaves duplicadas caso seja rodado mais de uma vez no banco de demo,
        // limpamos as tabelas principais envolvidas na demonstração.
        // AVISO: Isso só deve ser rodado no banco spivvps!
        $this->db->table('carteira_raw')->truncate();
        $this->db->table('vendor_users')->truncate();
        $this->db->table('vendor_notes')->truncate();
        $this->db->table('client_strategies')->truncate();
        $this->db->table('client_locations')->truncate();

        echo "Tabelas limpas com sucesso.\n";
        echo "Gerando Coordenadores e Vendedores...\n";

        // Gerar 3 Coordenadores
        $coordenadores = [];
        $se_opcoes = ['SE/SP', 'SE/RJ', 'SE/MG'];

        for ($i = 1; $i <= 3; $i++) {
            $matricula = 'C' . str_pad((string)$i, 4, '0', STR_PAD_LEFT);
            $coordenadores[] = [
                'matricula' => $matricula,
                'nome'      => $faker->name(),
                'email'     => $faker->companyEmail(),
                'perfil_vendedor' => 'COORDENADOR',
                'se'        => $se_opcoes[array_rand($se_opcoes)],
                'gerencia'  => 'GR ' . $faker->city(),
                'mtr_coordenador' => null,
                'nome_coordenador' => null,
                'gerencia_vendas' => 'Coordenação ' . $i,
                'ativo'     => true,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ];
        }

        // Gerar 20 Vendedores e atrelar aleatoriamente a um coordenador
        $vendedores = [];
        $perfis_vendedor = ['ACOM', 'GC', 'CEM'];

        for ($i = 1; $i <= 20; $i++) {
            $coord_pai = $coordenadores[array_rand($coordenadores)];
            
            $vendedores[] = [
                'matricula' => 'V' . str_pad((string)$i, 4, '0', STR_PAD_LEFT),
                'nome'      => $faker->name(),
                'email'     => $faker->companyEmail(),
                'perfil_vendedor' => $perfis_vendedor[array_rand($perfis_vendedor)],
                'se'        => $coord_pai['se'],
                'gerencia'  => $coord_pai['gerencia'],
                'mtr_coordenador' => $coord_pai['matricula'],
                'nome_coordenador' => $coord_pai['nome'],
                'gerencia_vendas' => $coord_pai['gerencia_vendas'],
                'ativo'     => true,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ];
        }

        // Inserir todos na tabela vendor_users
        $this->db->table('vendor_users')->insertBatch($coordenadores);
        $this->db->table('vendor_users')->insertBatch($vendedores);

        echo "Criados 3 coordenadores e 20 vendedores.\n";
        echo "Gerando 1000 clientes fictícios (carteira_raw)...\n";

        $categorias = ['DIAMANTE', 'OURO', 'PRATA', 'BRONZE'];
        $ciclos = ['Crescimento', 'Maturidade', 'Declínio', 'Recuperação'];
        $segmentos = ['E-commerce', 'Varejo', 'B2B', 'Indústria', 'Serviços', 'Saúde'];
        $canais = ['Portal', 'Agência', 'Representante', 'Direto'];

        $clientes_batch = [];

        for ($i = 1; $i <= 1000; $i++) {
            // Atribuir o cliente aleatoriamente a um dos 20 vendedores
            $vend = $vendedores[array_rand($vendedores)];
            
            $clientes_batch[] = [
                'se'                => $vend['se'],
                'id_grupo'          => (string)$faker->randomNumber(6, true),
                'grupo_cliente'     => 'Grupo ' . $faker->company(),
                'categoria'         => $categorias[array_rand($categorias)],
                'cnpj'              => $faker->cnpj(false), // Sem formatação (14 digitos puros)
                'razao_social'      => $faker->company() . ' ' . $faker->companySuffix(),
                'segmento_cliente'  => $segmentos[array_rand($segmentos)],
                'segmento_mercado'  => 'Comércio Varejista',
                'canais_vendas'     => $canais[array_rand($canais)],
                'canais_vendas_obs' => $faker->sentence(),
                'prospeccao'        => $faker->boolean(20) ? 'SIM' : 'NÃO',
                'forca_vendas_nome' => $vend['nome'],
                'matricula_mcmcu'   => $vend['matricula'],
                'conta_numero'      => (string)$faker->randomNumber(8, true),
                'conta_nome'        => 'Conta Comercial ' . $faker->word(),
                'ciclo_de_vida'     => $ciclos[array_rand($ciclos)],
                'cnae'              => (string)$faker->randomNumber(7, true),
                'cnae_desc'         => 'Atividades Comerciais ' . $faker->word(),
                'seg_merc_cnae'     => 'Varejo',
                'nat_juridica'      => 'LTDA',
                'gerencia'          => $vend['gerencia'],
                'mtr_cood'          => $vend['mtr_coordenador'],
                'nome_cood'         => $vend['nome_coordenador'],
                'gerencia_vendas'   => $vend['gerencia_vendas'],
                'forca_vendas_email'=> $vend['email'],
                'created_at'        => date('Y-m-d H:i:s'),
            ];

            // Inserir em lotes de 200 para evitar sobrecarga de memória
            if ($i % 200 == 0) {
                $this->db->table('carteira_raw')->insertBatch($clientes_batch);
                $clientes_batch = [];
                echo "Inseridos $i clientes...\n";
            }
        }

        if (!empty($clientes_batch)) {
            $this->db->table('carteira_raw')->insertBatch($clientes_batch);
        }

        echo "Carga de demonstração concluída com sucesso!\n";
        echo "Você pode fazer login com a matrícula V0001 (senha 123) para testar a visão de vendedor,\n";
        echo "ou C0001 (senha 123) para testar a visão de Coordenador.\n";
    }
}
