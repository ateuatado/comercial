# Documento de Arquitetura e Segurança - Projeto SPIV

## 1. Visão Geral e Objetivo
O SPIV é uma plataforma desenvolvida para modernizar e unificar a gestão da carteira de clientes dos Correios. Seu objetivo principal é fornecer uma ferramenta ágil, *mobile-first*, que permita à força de vendas (ACOM, GC, AGF) acessar, enriquecer e registrar informações sobre seus clientes diretamente em campo.

A plataforma substitui o uso de planilhas locais e relatórios estáticos por um sistema centralizado, garantindo rastreabilidade e governança sobre os dados comerciais da empresa.

## 2. Arquitetura Tecnológica
O sistema foi desenhado visando alta performance e segurança, utilizando tecnologias amplamente consolidadas no mercado.

- **Linguagem:** PHP 8+
- **Framework:** CodeIgniter 4 (Padrão MVC)
- **Banco de Dados:** PostgreSQL (Relacional, com suporte avançado a dados JSONB para enriquecimento futuro)
- **Frontend:** HTML5, CSS3, JavaScript Vanilla e Bootstrap 5 (Abordagem *Mobile-First*)
- **Hospedagem Alvo:** Ambiente interno e homologado de desenvolvimento/produção dos Correios.

## 3. Modelo de Dados e Isolamento
A estrutura de dados foi projetada para garantir que o isolamento de informações seja respeitado a nível de banco de dados e aplicação. 

As principais tabelas do ecossistema são:
- `carteira_raw`: Repositório central importado das bases analíticas, contendo os clientes distribuídos.
- `vendor_users`: Tabela de controle da força de vendas, segmentada por matrícula, gerência e coordenação.
- `client_wallets`: Tabela de relacionamento que atrela, de forma estrita, um CNPJ a uma Matrícula (responsável).

> [!IMPORTANT]
> **Isolamento de Visão (Multi-Tenancy lógico):**
> Todas as consultas (`SELECT`) no banco de dados injetam automaticamente a restrição `WHERE matricula_mcmcu = {matricula_logada}`. É estruturalmente impossível, através da camada de aplicação do vendedor, que um usuário acesse ou visualize a carteira comercial de outro vendedor.

## 4. Autenticação, Autorização e Identidade
O SPIV gerencia a identidade de forma delegada, visando alinhamento estrito às normativas de Segurança da Informação.

- **CodeIgniter Shield:** Framework oficial e auditado de segurança do CodeIgniter 4, utilizado para gestão da sessão, senhas (em hash seguro) e proteção contra ataques comuns (CSRF, XSS, Session Hijacking).
- **Integração LDAP:** O sistema conta com um módulo de autenticação preparado para consultar o Active Directory (LDAP) corporativo. As senhas de rede não são armazenadas localmente no banco do SPIV.
- **Auto-Provisionamento:** Ao autenticar via LDAP com sucesso, o sistema valida a matrícula do funcionário na base `vendor_users`. Caso o funcionário tenha perfil autorizado, sua conta local é instanciada e provisionada automaticamente com os perfis adequados (Ex: Vendedor, Coordenador, Admin).
- **Hierarquia Funcional:** Coordenadores possuem um painel próprio (`/coordenador`), onde uma trava via banco (`mtr_coordenador`) garante que apenas os vendedores pertencentes à sua lotação direta possam ter seus resumos visualizados.

## 5. Privacidade e LGPD (Privacy by Design)
A Lei Geral de Proteção de Dados (LGPD) foi o pilar de construção do fluxo de dados:

1. **Minimização de Dados Pessoais:** O sistema não trabalha com dados abertos de pessoas físicas (clientes). A base centraliza-se exclusivamente em CNPJs (pessoas jurídicas).
2. **Dados de Funcionários:** O único dado pessoal trafegado e armazenado da força de vendas é o *Nome*, *E-mail Corporativo* e a *Matrícula*. Não são armazenados CPFs, endereços residenciais ou dados sensíveis dos funcionários.
3. **Mapeamento ROPA:** A plataforma possui um inventário ROPA (Record of Processing Activities) embutido no projeto, descrevendo o ciclo de vida dos dados, base legal de tratamento (Legítimo Interesse / Execução de Contrato) e o tempo de retenção.
4. **Log e Rastreabilidade:** Qualquer movimentação de carteira (atribuição, registro de visita, alteração de status) é salva com carimbo de tempo (`created_at`) e o autor da ação, garantindo auditoria completa.

## 6. Considerações para o Ambiente Interno (CONEG)
Para que o sistema opere com sua máxima capacidade e segurança, requer-se:
- Servidor web Apache/Nginx ou IIS com suporte a reescrita de URL.
- Conexão segura na porta 5432 ao cluster PostgreSQL da infraestrutura.
- Abertura de porta LDAP (389 ou 636 para LDAPS) a partir do servidor de aplicação para validação das senhas de rede.
- Certificado SSL/TLS válido para que todo o tráfego da aplicação seja criptografado de ponta a ponta.
