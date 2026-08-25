# Plano de implementação

## Fase 0 — Descoberta e validações

- [ ] T001 Validar catálogo, nomes e regras dos três produtos.
- [ ] T002 Mapear APIs, SSO e campos de correlação dos sistemas corporativos.
- [ ] T003 Validar consentimentos, termos, retenção e tratamento documental.
- [ ] T004 Identificar fontes oficiais de ativação e primeiro uso.
- [ ] T005 Definir redes autorizadas, política offline e dispositivos pessoais.

## Fase 1 — Fundação

- [~] T006 Implementar campanhas, versões e publicação.
  - [x] T006.1 Criar campanha demonstrativa, vigência e estados fundamentais.
  - [ ] T006.2 Implementar versões e publicação após validar os conteúdos.
- [~] T007 Integrar identidade funcional e restrição de rede.
  - [x] T007.1 Separar empregado autenticável de vendedor da carteira.
  - [x] T007.2 Implementar provedor `demo` explícito com identidades fictícias.
  - [ ] T007.3 Conectar o provedor LDAP e validar restrição de rede no ambiente corporativo.
- [x] T007A Implementar catálogo de aplicações e concessões temporárias auditáveis.
- [x] T007B Implementar cálculo central de autorização por empregado, aplicação e campanha.
- [~] T008 Implementar adesão, treinamento, avaliação e termos.
  - [x] T008.1 Modelar adesão voluntária, estados e evidências versionadas.
  - [x] T008.2 Implementar regras de início e habilitação no domínio.
  - [ ] T008.3 Disponibilizar a jornada ao empregado após validar conteúdos e UX.
- [ ] T009 Implementar catálogo e questionário versionados.
- [ ] T010 Implementar oportunidade, correlação e linha do tempo imutável.

## Fase 2 — Prospecção sem atrito

- [ ] T011 Implementar registro rápido online e offline.
- [ ] T012 Consultar CNPJ e permitir confirmação de dados.
- [ ] T013 Implementar diagnóstico, regras explicáveis e recomendações.
- [ ] T014 Criar solicitação e reserva provisória de carteira.
- [ ] T015 Detectar duplicidades sem bloquear a jornada.

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
- [ ] T029 Executar testes de segurança, offline, idempotência e acessibilidade.
- [ ] T030 Preparar relatório e roteiro de demonstração aos decisores.

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
