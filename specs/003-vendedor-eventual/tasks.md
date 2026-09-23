# Plano de implementação

## Fase 0 — Descoberta e validações

- [ ] T001 Validar catálogo, nomes e regras dos três produtos.
- [ ] T002 Mapear APIs, SSO e campos de correlação dos sistemas corporativos.
- [ ] T003 Validar consentimentos, termos, retenção e tratamento documental.
- [ ] T004 Identificar fontes oficiais de ativação e primeiro uso.
- [ ] T005 Definir redes autorizadas, política offline e dispositivos pessoais.

## Fase 1 — Fundação

- [x] T006 Implementar campanhas, versões e publicação.
  - [x] T006.1 Criar campanha demonstrativa, vigência e estados fundamentais.
  - [x] T006.2 Implementar versões e publicação após validar os conteúdos. — Publicação e ativação exigem pacote coerente de capacitação, questionário e até três produtos, validado no servidor.
- [~] T007 Integrar identidade funcional e restrição de rede.
  - [x] T007.1 Separar empregado autenticável de vendedor da carteira.
  - [x] T007.2 Implementar provedor `demo` explícito com identidades fictícias.
  - [x] T007.2.1 Impedir nova autenticação sobre sessão Shield já ativa.
  - [x] T007.2.2 Compatibilizar `A0001`, `C0101–C0103` e `V0101–V0156` com o cadastro fechado de empregados, sem recriar senhas, grupos ou carteiras.
  - [ ] T007.3 Conectar o provedor LDAP e validar restrição de rede no ambiente corporativo.
- [x] T007A Implementar catálogo de aplicações e concessões temporárias auditáveis.
  - [x] T007A.1 Exibir o estado efetivo da trava global na administração e manter o conteúdo da página em container responsivo centralizado.
  - [x] T007A.2 Normalizar booleanos retornados pelo PostgreSQL no controle administrativo de aplicações.
- [x] T007B Implementar cálculo central de autorização por empregado, aplicação e campanha.
- [x] T008 Implementar adesão, treinamento, avaliação e termos.
  - [x] T008.1 Modelar adesão voluntária, estados e evidências versionadas.
  - [x] T008.2 Implementar regras de início e habilitação no domínio.
  - [x] T008.3 Disponibilizar a jornada ao empregado após validar conteúdos e UX.
    - [x] T008.3.1 Exibir campanhas elegíveis e registrar adesão voluntária.
    - [x] T008.3.2 Sinalizar capacitação pendente sem publicar conteúdo não validado.
    - [x] T008.3.3 Publicar termos, capacitação e avaliação versionados após validação do gestor.
  - [x] T008.4 Implementar pausa, retomada, encerramento voluntário e suspensão administrativa auditáveis.
- [x] T009 Implementar catálogo e questionário versionados.
- [x] T010 Implementar oportunidade, correlação e linha do tempo imutável.

## Fase 2 — Prospecção sem atrito

- [x] T011 Implementar registro rápido online e offline. — UUID gerado antes do envio, fila temporária no navegador com expiração de 24 horas, sincronização automática idempotente e separação entre instante do contato e recebimento no servidor.
- [x] T012 Consultar CNPJ e permitir confirmação de dados. — Consulta nas fontes locais reais (`carteira_raw` e Receita Federal), confirmação obrigatória após conferência com o cliente e evidência imutável com fonte, instante e snapshot no evento inicial.
- [x] T013 Implementar diagnóstico, regras explicáveis e recomendações.
- [x] T014 Criar solicitação e reserva provisória de carteira. — Solicitação por oportunidade, reserva técnica por CNPJ e evento auditável, sem escrita no domínio de carteira.
- [x] T015 Detectar duplicidades sem bloquear a jornada. — Alertas de carteira atribuída, oportunidade ativa na campanha e reserva técnica pendente são registrados no evento inicial e exibidos internamente, sem impedir a criação nem as próximas etapas.

## Fase 3 — Cliente e contratação

- [ ] T016 Implementar consentimento progressivo e QR Code.
- [ ] T017 Implementar portal externo com token temporário.
- [ ] T018 Implementar checklist e envio protegido de documentos.
- [ ] T019 Integrar ou assistir cadastro, contrato e assinatura oficiais.
- [ ] T020 Implementar correlação e fila de reconciliação.

## Fase 4 — Colaboração e pós-venda

- [ ] T021 Implementar pedido de orientação, colaboração e transferência.
- [ ] T022 Implementar estados de ativação e primeiro uso.
- [ ] T023 Gerar tarefas de pós-venda e encaminhamento de suporte.
- [ ] T024 Implementar pesquisa de satisfação e nova oportunidade.

## Fase 5 — Demonstração e aprendizado

- [ ] T025 Implementar painel individual.
- [ ] T026 Implementar funil e painel gerencial.
- [ ] T027 Implementar avaliação demonstrativa de reconhecimento com aviso.
- [ ] T028 Instrumentar abandono, erros e desempenho das recomendações.
- [~] T029 Executar testes de segurança, offline, idempotência e acessibilidade.
  - [x] T029.1 Estabelecer baseline automatizada da demonstração interna e validar a criação de campanhas com códigos aceitos e rejeitados.
  - [ ] T029.2 Concluir a matriz de segurança, offline, idempotência e acessibilidade antes do piloto corporativo.
- [~] T030 Preparar relatório e roteiro de demonstração aos decisores.
  - [x] T030.1 Documentar o runbook de implantação, ativação gradual, monitoramento e retorno da demonstração interna.
  - [ ] T030.2 Preparar o relatório de resultados e o roteiro executivo da demonstração.

## Dependências críticas

- T002 condiciona o nível de automação de T019 e T020.
- T003 condiciona a publicação de T016 a T018.
- T007 e T010 são pré-requisitos para qualquer dado real.
- O piloto pode usar fluxo assistido se APIs não estiverem disponíveis, desde que
  correlação, auditoria e limitações estejam explícitas.

## Definição de pronto do piloto

- Empregado ativo adere e conclui capacitação.
- Oportunidade pode ser criada em até um minuto, inclusive offline.
- Cliente com CNPJ recebe recomendação e link seguro.
- Contratação oficial pode ser conduzida ou assistida sem perda de autoria.
- Conflito de carteira não bloqueia o cliente.
- Ativação e primeiro uso alimentam pós-venda e painel.
- Resultados demonstrativos não exibem promessa financeira.
- Logs permitem reconstruir integralmente uma jornada.
