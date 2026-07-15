<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Seeder: SystemMessagesSeeder
 * Cria mensagens padrão do sistema.
 */
class SystemMessagesSeeder extends Seeder
{
    public function run(): void
    {
        $db = $this->db;

        $messages = [
            [
                'slug'     => 'sem-carteira',
                'titulo'   => 'Acesso ao SPIV',
                'conteudo' => '<p>Olá! 👋</p>
<p>Sua matrícula ainda <strong>não possui uma carteira de clientes</strong> vinculada no SPIV.</p>
<p>Isso pode acontecer porque:</p>
<ul>
    <li>Você ainda não foi designado como responsável por uma carteira;</li>
    <li>Sua matrícula não consta na base de distribuição atual;</li>
    <li>Houve uma reclassificação recente na sua SE.</li>
</ul>
<p>Se você acredita que deveria ter acesso, entre em contato com seu <strong>coordenador</strong> ou com a <strong>equipe de gestão de carteiras</strong> da sua superintendência.</p>
<p><em>— Equipe SPIV</em></p>',
                'ativo'    => true,
            ],
            [
                'slug'     => 'manutencao',
                'titulo'   => 'Sistema em Manutenção',
                'conteudo' => '<p>O SPIV está em <strong>manutenção programada</strong>.</p>
<p>Voltaremos em breve. Obrigado pela compreensão.</p>
<p><em>— Equipe SPIV</em></p>',
                'ativo'    => false,
            ],
            [
                'slug'     => 'boas-vindas',
                'titulo'   => 'Bem-vindo ao SPIV',
                'conteudo' => '<p>Bem-vindo ao <strong>SPIV — Sistema de Prospecção e Inteligência de Vendas</strong>! 🚀</p>
<p>Aqui você encontra sua carteira de clientes com dados enriquecidos, ferramentas de prospecção e registro de visitas.</p>
<p>Dúvidas? Fale com seu coordenador.</p>
<p><em>— Equipe SPIV</em></p>',
                'ativo'    => true,
            ],
        ];

        $inserted = 0;
        $skipped  = 0;

        foreach ($messages as $msg) {
            $exists = $db->query("SELECT id FROM system_messages WHERE slug = ? LIMIT 1", [$msg['slug']])->getRow();

            if ($exists) {
                $skipped++;
                continue;
            }

            $db->query(
                "INSERT INTO system_messages (slug, titulo, conteudo, ativo, created_at, updated_at) VALUES (?, ?, ?, ?, NOW(), NOW())",
                [$msg['slug'], $msg['titulo'], $msg['conteudo'], $msg['ativo'] ? 'true' : 'false']
            );
            $inserted++;
        }

        echo "SystemMessagesSeeder: {$inserted} inseridas, {$skipped} já existentes." . PHP_EOL;
    }
}
