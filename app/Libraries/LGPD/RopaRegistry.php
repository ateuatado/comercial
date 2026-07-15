<?php

declare(strict_types=1);

namespace App\Libraries\LGPD;

/**
 * Registry estático para fornecer a lista de operações ROPA realizadas pelo sistema SPIV.
 */
class RopaRegistry
{
    private const CONTROLADOR = 'Empresa Brasileira de Correios e Telégrafos (CNPJ: 34.028.316/0001-03)';
    
    private const MEDIDAS_SEGURANCA_PADRAO = [
        'Criptografia TLS em trânsito',
        'Controle de Acesso Baseado em Funções (RBAC)',
        'Isolamento de banco de dados em rede privada',
        'Logs de auditoria de autenticação e ações destrutivas'
    ];

    private const DIREITOS_TITULARES = 'Solicitações tratadas via portal do titular ou via Encarregado (DPO) através dos canais oficiais dos Correios.';

    /**
     * Retorna a coleção completa de registros ROPA mapeados no SPIV.
     * 
     * @return ROPA[]
     */
    public static function getAll(): array
    {
        return [
            self::getVendedoresRopa(),
            self::getProspeccaoAntifraudeRopa(),
            self::getAutenticacaoRopa(),
        ];
    }

    private static function getVendedoresRopa(): ROPA
    {
        return new ROPA(
            identificacaoControlador: self::CONTROLADOR,
            processoTratamento: 'Gestão de Vendedores e Distribuição de Carteiras',
            categoriasTitulares: ['Empregados (ACOMs, Gerentes de Conta, Supervisores)'],
            categoriasDadosPessoais: ['Nome Completo', 'Matrícula', 'Lotação', 'ID de Usuário'],
            finalidadeTratamento: 'Permitir o acesso autenticado ao sistema SPIV, controlar permissões (autorização) e atribuir carteiras de clientes de forma auditável.',
            baseLegal: 'Art. 7º, V - Execução de Contrato / Art. 7º, IX - Legítimo Interesse (Gestão Administrativa)',
            compartilhamentoDados: ['Sem compartilhamento externo. Tratamento 100% interno.'],
            transferenciaInternacional: 'Não há',
            prazoRetencaoDescarte: '5 anos após o desligamento do colaborador ou revogação do acesso, conforme tabela de temporalidade interna, descartado via deleção em banco (soft-delete inicialmente, purgado posteriormente).',
            medidasSeguranca: self::MEDIDAS_SEGURANCA_PADRAO,
            atendimentoDireitosTitulares: self::DIREITOS_TITULARES
        );
    }

    private static function getProspeccaoAntifraudeRopa(): ROPA
    {
        return new ROPA(
            identificacaoControlador: self::CONTROLADOR,
            processoTratamento: 'Prospecção Antifraude (Análise de Suspeitas)',
            categoriasTitulares: ['Sócios de empresas prospectadas', 'Terceiros relacionados'],
            categoriasDadosPessoais: ['CPF', 'Nome (quando em razão social)', 'Histórico de Vínculos Societários'],
            finalidadeTratamento: 'Prevenir fraudes, mitigar riscos financeiros e proteger o crédito na concessão de serviços postais faturados.',
            baseLegal: 'Art. 7º, X - Proteção ao Crédito / Art. 7º, IX - Legítimo Interesse (Prevenção à Fraude)',
            compartilhamentoDados: ['Consultas eventuais a birôs de crédito e órgãos governamentais de validação (ex: Receita Federal).'],
            transferenciaInternacional: 'Não há',
            prazoRetencaoDescarte: '5 anos após o registro da suspeita, retido por obrigação legal e prevenção contínua. Descarte via exclusão lógica e posterior expurgo seguro.',
            medidasSeguranca: array_merge(self::MEDIDAS_SEGURANCA_PADRAO, ['Acesso restrito apenas a perfis de nível Administrador e Supervisor']),
            atendimentoDireitosTitulares: self::DIREITOS_TITULARES
        );
    }

    private static function getAutenticacaoRopa(): ROPA
    {
        return new ROPA(
            identificacaoControlador: self::CONTROLADOR,
            processoTratamento: 'Autenticação de Usuários e Auditoria (Logs)',
            categoriasTitulares: ['Empregados autorizados a usar o sistema'],
            categoriasDadosPessoais: ['Endereço de E-mail Corporativo', 'Senha (com Hash bcrypt/argon2)', 'Endereço IP', 'User-Agent', 'Data/Hora de Acesso'],
            finalidadeTratamento: 'Autenticar o acesso ao sistema corporativo, garantir o não-repúdio e gerar trilhas de auditoria para investigações de segurança da informação.',
            baseLegal: 'Art. 7º, IX - Legítimo Interesse (Segurança da Informação) / Art. 7º, II - Cumprimento de Obrigação Legal (Art. 15 Marco Civil da Internet)',
            compartilhamentoDados: ['Sem compartilhamento externo. Logs gerenciados pela infraestrutura central de TI dos Correios.'],
            transferenciaInternacional: 'Não há',
            prazoRetencaoDescarte: '6 meses mínimos para logs de acesso (Marco Civil), mantidos em cold storage por até 5 anos para investigações de incidentes de segurança.',
            medidasSeguranca: array_merge(self::MEDIDAS_SEGURANCA_PADRAO, ['Hashing forte de senhas (não reversível)', 'Monitoramento de falhas de login (Brute-force protection)']),
            atendimentoDireitosTitulares: self::DIREITOS_TITULARES
        );
    }
}
