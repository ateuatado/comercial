<?php

declare(strict_types=1);

namespace App\Libraries;

/**
 * LdapService — Autenticação via LDAP da rede interna dos Correios.
 *
 * ============================================================
 * STATUS: STUB — implementar quando estiver na rede dos Correios.
 * ============================================================
 *
 * Configuração necessária no .env para ativar:
 *
 *   LDAP_ENABLED   = true
 *   LDAP_HOST      = ldap.correios.com.br          # endereço do servidor LDAP
 *   LDAP_PORT      = 389                            # 389 = LDAP | 636 = LDAPS
 *   LDAP_DN_PATTERN = CN={username},OU=Usuarios,DC=correios,DC=com,DC=br
 *   LDAP_BASE_DN   = DC=correios,DC=com,DC=br
 *
 * Pré-requisito no servidor:
 *   - Extensão PHP LDAP habilitada (extension=ldap no php.ini)
 *   - Acesso à porta LDAP_PORT no servidor LDAP_HOST
 *
 * Como usar:
 *   $ldap = new LdapService();
 *   if ($ldap->authenticate('89056584', 'senha_do_correios')) {
 *       // credenciais válidas — prosseguir com o login
 *   }
 */
class LdapService
{
    private string $host;
    private int    $port;
    private string $dnPattern;

    public function __construct()
    {
        $this->host      = (string) env('LDAP_HOST', 'ldap.correios.com.br');
        $this->port      = (int)    env('LDAP_PORT', 389);
        $this->dnPattern = (string) env('LDAP_DN_PATTERN', 'CN={username},OU=Usuarios,DC=correios,DC=com,DC=br');
    }

    /**
     * Valida matrícula e senha contra o LDAP dos Correios.
     *
     * Retorna true quando as credenciais são válidas.
     * Retorna false em qualquer falha (credenciais inválidas, servidor
     * indisponível, extensão LDAP ausente).
     *
     * TODO (na rede dos Correios):
     *   1. Confirmar o host, port e DN_PATTERN corretos.
     *   2. Verificar se é LDAPS (port 636) ou LDAP (port 389).
     *   3. Se o servidor exige TLS, adicionar ldap_start_tls($conn) antes do bind.
     *   4. Se o DN pattern for diferente (ex.: user@correios.com.br), ajustar.
     *   5. Testar com: ldapsearch -H ldap://LDAP_HOST -D "DN" -w "senha" -b "BASE_DN"
     */
    public function authenticate(string $matricula, string $password): bool
    {
        // Segurança: rejeitar senha vazia (LDAP anônimo seria aceito como bind).
        if (trim($password) === '') {
            return false;
        }

        if (! function_exists('ldap_connect')) {
            log_message('error', '[LdapService] Extensão PHP LDAP não está habilitada.');
            return false;
        }

        $connection = @ldap_connect($this->host, $this->port);
        if ($connection === false) {
            log_message('error', "[LdapService] Não foi possível conectar em {$this->host}:{$this->port}");
            return false;
        }

        ldap_set_option($connection, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($connection, LDAP_OPT_REFERRALS, 0);

        $dn = str_replace('{username}', ldap_escape($matricula, '', LDAP_ESCAPE_DN), $this->dnPattern);

        try {
            $bound = @ldap_bind($connection, $dn, $password);
        } catch (\Throwable $e) {
            log_message('error', '[LdapService] Exceção no bind: ' . $e->getMessage());
            $bound = false;
        } finally {
            @ldap_unbind($connection);
        }

        if (! $bound) {
            log_message('info', "[LdapService] Bind falhou para matrícula: {$matricula}");
        }

        return (bool) $bound;
    }
}
