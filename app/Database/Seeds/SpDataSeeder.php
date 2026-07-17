<?php

declare(strict_types=1);

namespace App\Database\Seeds;

use CodeIgniter\CLI\CLI;
use CodeIgniter\Database\Seeder;
use CodeIgniter\Shield\Entities\User;
use CodeIgniter\Shield\Models\UserModel;

class SpDataSeeder extends Seeder
{
    public function run(): void
    {
        $db = db_connect();
        
        CLI::write("=== Iniciando População de São Paulo (RMSP/Interior) ===", 'yellow');
        
        // 1. Limpar todas as tabelas comerciais e de autenticação (mantendo a estrutura limpa)
        CLI::write("Limpando tabelas atuais...", 'cyan');
        $tablesToTruncate = [
            'prospecting_reviews',
            'prospecting_flags',
            'client_potentiality',
            'client_status_history',
            'wallet_movements',
            'client_strategies',
            'client_locations',
            'client_wallets',
            'carteira_raw',
            'vendors',
            'vendor_users',
            'auth_identities',
            'users'
        ];
        
        foreach ($tablesToTruncate as $tbl) {
            $db->query("TRUNCATE TABLE public.{$tbl} RESTART IDENTITY CASCADE;");
        }
        
        // 2. Recriar o Administrador Geral Fictício
        CLI::write("Criando Administrador Fictício (A0001)...", 'cyan');
        /** @var UserModel $userModel */
        $userModel = model(UserModel::class);
        $adminUser = new User([
            'username' => 'A0001',
            'email'    => 'A0001@spiv.dev',
            'password' => '123',
            'active'   => 1,
        ]);
        $userModel->save($adminUser);
        $adminId = $userModel->getInsertID();
        $savedAdmin = $userModel->findById($adminId);
        $savedAdmin->addGroup('admin');
        
        // 3. Criar 3 Coordenadores Fictícios
        CLI::write("Criando 3 Coordenadores (C0101 a C0103)...", 'cyan');
        $coordinators = [
            ['username' => 'C0101', 'nome' => 'Coord. Metropolitano SPM-A', 'gerencia' => 'GR Metropolitana A'],
            ['username' => 'C0102', 'nome' => 'Coord. Metropolitano SPM-B', 'gerencia' => 'GR Metropolitana B'],
            ['username' => 'C0103', 'nome' => 'Coord. Interior SPI-A', 'gerencia' => 'GR Interior Campinas'],
        ];
        
        $coordMap = [];
        foreach ($coordinators as $cData) {
            $user = new User([
                'username' => $cData['username'],
                'email'    => $cData['username'] . '@spiv.dev',
                'password' => '123',
                'active'   => 1,
            ]);
            $userModel->save($user);
            $userId = $userModel->getInsertID();
            $saved = $userModel->findById($userId);
            $saved->addGroup('acom');
            
            $coordMap[$cData['username']] = [
                'id' => $userId,
                'nome' => $cData['nome'],
                'gerencia' => $cData['gerencia']
            ];
        }
        
        // 4. Criar 56 Vendedores Fictícios
        CLI::write("Criando 56 Vendedores (V0101 a V0156) com perfis ACOM I, II, III...", 'cyan');
        $vendorsList = [];
        $count = 1;
        for ($i = 101; $i <= 156; $i++) {
            $username = 'V' . str_pad((string)$i, 4, '0', STR_PAD_LEFT);
            
            // Definir perfil do vendedor (ACOM I, II, III)
            if ($count <= 18) {
                $tipoAcom = 'I';
                $label = "ACOM I";
            } elseif ($count <= 37) {
                $tipoAcom = 'II';
                $label = "ACOM II";
            } else {
                $tipoAcom = 'III';
                $label = "ACOM III";
            }
            
            // Distribuir entre os 3 coordenadores
            if ($count <= 18) {
                $coordCode = 'C0101';
            } elseif ($count <= 37) {
                $coordCode = 'C0102';
            } else {
                $coordCode = 'C0103';
            }
            
            $user = new User([
                'username' => $username,
                'email'    => $username . '@spiv.dev',
                'password' => '123',
                'active'   => 1,
            ]);
            $userModel->save($user);
            $userId = $userModel->getInsertID();
            $saved = $userModel->findById($userId);
            $saved->addGroup('acom');
            
            // Criar registro na tabela vendors
            $db->table('vendors')->insert([
                'user_id'    => $userId,
                'matricula'  => $username,
                'nome'       => "Vendedor Fictício {$username} ({$label})",
                'tipo_acom'  => $tipoAcom,
                'estado_se'  => 'SP',
                'ativo'      => true,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
            $vendorDbId = $db->insertID();
            
            // Criar registro na tabela vendor_users
            $db->table('vendor_users')->insert([
                'matricula'        => $username,
                'nome'             => "Vendedor Fictício {$username} ({$label})",
                'email'            => $username . '@spiv.dev',
                'perfil_vendedor'  => "ACOM {$tipoAcom}",
                'se'               => ($coordCode === 'C0103') ? 'SPI' : 'SPM',
                'gerencia'         => $coordMap[$coordCode]['gerencia'],
                'mtr_coordenador'  => $coordCode,
                'nome_coordenador' => $coordMap[$coordCode]['nome'],
                'gerencia_vendas'  => 'GEVEN SP',
                'shield_user_id'   => $userId,
                'ativo'            => true,
                'created_at'       => date('Y-m-d H:i:s'),
                'updated_at'       => date('Y-m-d H:i:s'),
            ]);
            
            $vendorsList[] = [
                'id' => $vendorDbId,
                'matricula' => $username,
                'nome' => "Vendedor Fictício {$username} ({$label})",
                'se' => ($coordCode === 'C0103') ? 'SPI' : 'SPM',
                'gerencia' => $coordMap[$coordCode]['gerencia'],
                'mtr_cood' => $coordCode,
                'nome_cood' => $coordMap[$coordCode]['nome']
            ];
            $count++;
        }
        
        // 5. Selecionar Estabelecimentos em SP
        CLI::write("Buscando estabelecimentos ativos em SP...", 'cyan');
        
        $sql = "
            SELECT e.cnpj_basico, e.cnpj_ordem, e.cnpj_dv, e.municipio, e.cnae_fiscal_principal, emp.razao_social, e.nome_fantasia
            FROM receita.estabelecimentos e
            LEFT JOIN receita.empresas emp ON e.cnpj_basico = emp.cnpj_basico
            WHERE e.uf = 'SP'
            LIMIT 12000
        ";
        
        $results = $db->query($sql)->getResultArray();
        $totalClients = count($results);
        CLI::write("Total de estabelecimentos carregados: {$totalClients}", 'yellow');
        
        if ($totalClients < 11200) {
            CLI::error("ERRO: Quantidade insuficiente de estabelecimentos ativos em SP para atender a regra de 200 por vendedor (necessário 11.200, encontrado {$totalClients}).");
            return;
        }
        
        // Lista de municípios da Região Metropolitana de São Paulo (RMSP)
        $rmspCodes = [
            '7107', '6477', '7075', '7057', '6789', '6377', '6691', '6313', '6747', '7157', 
            '6213', '6529', '6361', '7049', '6565', '6251', '6119', '7065', '6425', '6607', 
            '6517', '6913', '6995', '7093', '6393', '6395', '6553', '6947', '7037', '7069', 
            '6285', '6915', '6207', '6469', '7225'
        ];
        
        $categories = ['BRONZE', 'PRATA', 'OURO', 'DIAMANTE'];
        $lifeCycles = ['Crescimento', 'Maturidade', 'Declínio', 'Recuperação'];
        $segments = ['E-commerce', 'Varejo', 'Saúde', 'Serviços', 'Indústria', 'B2B'];
        
        CLI::write("Distribuindo 200+ clientes para cada um dos 56 vendedores...", 'cyan');
        
        $batchCarteiraRaw = [];
        $batchClientWallets = [];
        
        $clientIdx = 0;
        $now = date('Y-m-d H:i:s');
        
        foreach ($vendorsList as $vendor) {
            // Garantir exatamente 200 clientes para este vendedor
            for ($k = 0; $k < 200; $k++) {
                if ($clientIdx >= $totalClients) {
                    break;
                }
                
                $emp = $results[$clientIdx];
                $cnpj = $emp['cnpj_basico'] . $emp['cnpj_ordem'] . $emp['cnpj_dv'];
                
                // Determinar SE com base no município (RMSP = SPM | Outros = SPI)
                $se = in_array((string)$emp['municipio'], $rmspCodes, true) ? 'SPM' : 'SPI';
                
                $categoria = $categories[array_rand($categories)];
                $ciclo = $lifeCycles[array_rand($lifeCycles)];
                $segmento = $segments[array_rand($segments)];
                
                $batchCarteiraRaw[] = [
                    'se' => $se,
                    'id_grupo' => 'G' . rand(1000, 9999),
                    'grupo_cliente' => 'Grupo Fictício ' . rand(1, 100),
                    'categoria' => $categoria,
                    'cnpj' => $cnpj,
                    'razao_social' => $emp['razao_social'] ?: ($emp['nome_fantasia'] ?: ('Empresa Fictícia ' . $cnpj)),
                    'segmento_cliente' => $segmento,
                    'segmento_mercado' => 'Comércio e Serviços Gerais',
                    'canais_vendas' => 'Direto',
                    'prospeccao' => 'NÃO',
                    'forca_vendas_nome' => $vendor['nome'],
                    'matricula_mcmcu' => $vendor['matricula'],
                    'conta_numero' => (string)rand(100000, 999999),
                    'conta_nome' => 'Conta ' . $cnpj,
                    'ciclo_de_vida' => $ciclo,
                    'cnae' => $emp['cnae_fiscal_principal'] ?? '0000000',
                    'cnae_desc' => 'Atividades Comerciais Gerais',
                    'seg_merc_cnae' => 'Geral',
                    'nat_juridica' => 'LTDA',
                    'gerencia' => $vendor['gerencia'],
                    'mtr_cood' => $vendor['mtr_cood'],
                    'nome_cood' => $vendor['nome_cood'],
                    'gerencia_vendas' => 'GEVEN SP',
                    'forca_vendas_email' => $vendor['matricula'] . '@spiv.dev',
                    'created_at' => $now,
                ];
                
                $batchClientWallets[] = [
                    'cnpj' => $cnpj,
                    'vendor_id' => $vendor['id'],
                    'status_operacional' => 'novo',
                    'origem_atribuicao' => 'automatica',
                    'atribuido_em' => $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
                
                $clientIdx++;
            }
        }
        
        // Salvar em lotes para performance
        CLI::write("Inserindo registros na carteira_raw...", 'cyan');
        $db->table('carteira_raw')->insertBatch($batchCarteiraRaw);
        
        CLI::write("Inserindo registros na client_wallets...", 'cyan');
        $db->table('client_wallets')->insertBatch($batchClientWallets);
        
        CLI::write("População de dados de São Paulo concluída!", 'green');
        CLI::write("Total de Vendedores: 56", 'green');
        CLI::write("Total de Clientes Criados: " . count($batchCarteiraRaw), 'green');
        CLI::write("Cada vendedor possui exatamente 200 clientes vinculados.", 'green');
    }
}
