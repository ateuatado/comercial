# Instruções para agentes de IA

Leia este arquivo antes de qualquer alteração significativa no projeto.

## Contexto do projeto

Este repositório contém o SPIV, uma aplicação baseada em CodeIgniter 4 com PostgreSQL, Shield e integração com LDAP para uso interno.

O foco atual é o MVP da carteira de clientes, conforme definido em:
- [specs/001-carteira-clientes/spec.md](specs/001-carteira-clientes/spec.md)
- [specs/001-carteira-clientes/plan.md](specs/001-carteira-clientes/plan.md)
- [specs/001-carteira-clientes/tasks.md](specs/001-carteira-clientes/tasks.md)

## Prioridades atuais

1. Autenticação e perfis
2. Modelagem de dados da carteira
3. Cadastro e gestão de vendedores
4. Distribuição automática e manual
5. Portal operacional do ACOM
6. Status operacional e histórico
7. Prospecção antifraude
8. Área administrativa e métricas

## Regras para implementação

- Preserve a arquitetura MVC atual do CodeIgniter 4.
- Mantenha mudanças pequenas e bem isoladas.
- Documente decisões de negócio no escopo da spec antes de implementar alterações amplas.
- Sempre que uma tarefa for concluída, atualize a checklist em [specs/001-carteira-clientes/tasks.md](specs/001-carteira-clientes/tasks.md).
- Antes de iniciar um trabalho relevante, leia esta instrução, a constitution, a spec, o plano e as tasks do feature atual.
- Use a checklist como fonte única de progresso e mantenha os status alinhados com o que foi realmente implementado.

## Estratégia de acompanhamento de tarefas

- Use a checklist em [specs/001-carteira-clientes/tasks.md](specs/001-carteira-clientes/tasks.md) como fonte única de progresso.
- Status permitidos:
  - [ ] Não iniciado
  - [~] Em andamento
  - [x] Concluído
  - [!] Bloqueado
- Marque uma tarefa como concluída somente quando houver evidência real de implementação e validação.
- Se uma tarefa depender de outra, registre isso na própria linha da checklist.
- Quando uma tarefa for alterada, mantenha a descrição objetiva e o estado atual alinhado com o que foi realmente feito.

## Direção de trabalho

O MVP inicial deve priorizar:
- autenticação e permissões
- estrutura de dados da carteira
- CRUD de vendedores
- distribuição inicial com base em capital social

A partir daí, o trabalho pode avançar para portal operacional, status, antifraude e área administrativa.
