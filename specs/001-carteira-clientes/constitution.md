# Constitution 001 - Carteira de Clientes SPIV

## Objetivo

Esta constituição define as regras de engenharia e de produto que devem guiar o desenvolvimento do MVP da carteira de clientes do SPIV.

## Princípios não negociáveis

1. Preservar a arquitetura MVC do CodeIgniter 4.
   - Controllers devem permanecer finos e voltados à orquestração.
   - Regras de negócio devem residir em models ou serviços pequenos.
   - Evitar abstrações desnecessárias.

2. Trabalhar sobre a base existente de clientes e CNPJs já importada no PostgreSQL.
   - Não reimplementar ingestão inicial neste escopo.
   - Usar a base já disponível como fonte operacional da carteira.

3. Priorizar segurança e isolamento por perfil.
   - Admin, ACOM e Gerente de Conta devem ter visões e permissões diferentes.
   - O acesso a dados deve ser restrito ao escopo do usuário autenticado.

4. Garantir rastreabilidade operacional.
   - Toda distribuição, reatribuição, alteração de status e suspeita deve deixar histórico audível.
   - A auditoria deve ser tratada como parte do MVP, não como item opcional.

5. Manter o MVP simples e funcional.
   - O foco inicial é autenticação, estrutura de carteira, vendedores, distribuição e status.
   - Funcionalidades avançadas de enriquecimento, IA e regras sofisticadas ficam para fases futuras.

6. Usar dados reais e já disponíveis sempre que possível.
   - No MVP, o critério de balanceamento inicial deve usar o capital social da tabela empresas.
   - Novas fontes de informação podem ser introduzidas depois, sem quebrar a lógica base.

7. Validar mudanças antes de considerar a tarefa concluída.
   - Toda implementação relevante deve ser acompanhada de validação prática ou teste.
   - A conclusão de uma tarefa depende de evidência real, não apenas de código escrito.

## Diretrizes de execução

- Novas decisões de negócio devem ser registradas na spec antes de virar implementação.
- Mudanças amplas devem ser pequenas e isoladas.
- O arquivo de tasks é a fonte única de acompanhamento do progresso.
- Qualquer agente ou desenvolvedor que retome o projeto deve ler esta constituição, a spec, o plano e as tasks antes de agir.
