<?php

declare(strict_types=1);

/**
 * This file is part of CodeIgniter Shield.
 *
 * (c) CodeIgniter Foundation <admin@codeigniter.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Config;

use CodeIgniter\Shield\Config\AuthGroups as ShieldAuthGroups;

class AuthGroups extends ShieldAuthGroups
{
    /**
     * --------------------------------------------------------------------
     * Default Group
     * --------------------------------------------------------------------
     * The group that a newly registered user is added to.
     */
    // Grupo padrão atribuído a novos usuários criados pelo admin.
    // Na prática, o admin sempre atribui o grupo correto ao criar.
    public string $defaultGroup = 'acom';

    /**
     * --------------------------------------------------------------------
     * Groups
     * --------------------------------------------------------------------
     * Perfis de acesso do SPIV conforme spec 001 - Carteira de Clientes.
     *
     * - admin:          Administra vendedores, distribui carteiras e aprova suspeitas.
     * - acom:           Acessa apenas a própria carteira de clientes.
     * - gerente_conta:  Atende clientes de maior receita/potencial. Mesma base do ACOM no MVP.
     *
     * @var array<string, array<string, string>>
     */
    public array $groups = [
        'admin' => [
            'title'       => 'Administrador',
            'description' => 'Administra vendedores, distribui carteiras e aprova suspeitas.',
        ],
        'acom' => [
            'title'       => 'ACOM',
            'description' => 'Acessa apenas a própria carteira de clientes.',
        ],
        'gerente_conta' => [
            'title'       => 'Gerente de Conta',
            'description' => 'Atende clientes de maior receita ou potencial.',
        ],
    ];

    /**
     * --------------------------------------------------------------------
     * Permissions
     * --------------------------------------------------------------------
     * Permissões disponíveis no sistema conforme spec 001.
     *
     * - admin.access:           Acesso à área administrativa.
     * - vendors.manage:         Criar, editar e desativar vendedores.
     * - wallet.distribute:      Disparar distribuição automática de carteira.
     * - wallet.reassign:        Reatribuir clientes manualmente com auditoria.
     * - wallet.view_all:        Ver a carteira consolidada de todos os ACOMs.
     * - wallet.view_own:        Ver apenas a própria carteira.
     * - status.manage_restricted: Executar transições de status restritas (bloqueio/inativação).
     * - prospecting.approve:    Aprovar ou liberar suspeitas de prospecção.
     * - supervisor:             Permissão extra do admin para governança de suspeitas.
     */
    public array $permissions = [
        'admin.access'              => 'Acesso à área administrativa',
        'vendors.manage'            => 'Criar, editar e desativar vendedores',
        'wallet.distribute'         => 'Disparar distribuição automática de carteira',
        'wallet.reassign'           => 'Reatribuir clientes manualmente com auditoria',
        'wallet.view_all'           => 'Ver a carteira consolidada de todos os responsáveis',
        'wallet.view_own'           => 'Ver apenas a própria carteira',
        'status.manage_restricted'  => 'Executar transições de status restritas ao admin',
        'prospecting.approve'       => 'Aprovar ou liberar suspeitas de prospecção',
        'supervisor'                => 'Permissão extra de governança de suspeitas',
    ];

    /**
     * --------------------------------------------------------------------
     * Permissions Matrix
     * --------------------------------------------------------------------
     * Mapeamento de permissões por grupo.
     *
     * O atributo 'supervisor' não é um grupo separado; é concedido
     * individualmente pelo admin a usuários específicos.
     */
    public array $matrix = [
        'admin' => [
            'admin.access',
            'vendors.manage',
            'wallet.distribute',
            'wallet.reassign',
            'wallet.view_all',
            'status.manage_restricted',
            'prospecting.approve',
        ],
        'acom' => [
            'wallet.view_own',
        ],
        'gerente_conta' => [
            'wallet.view_own',
        ],
    ];
}
