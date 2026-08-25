# Especificação — Vendedor Eventual

**Feature:** 003-vendedor-eventual  
**Status:** especificação inicial  
**Data:** 2026-08-25

## 1. Visão

Transformar o SPIV em uma plataforma comercial ponta a ponta na qual qualquer
empregado ativo possa aderir voluntariamente como Vendedor Eventual, identificar
uma necessidade durante seu contato com uma empresa, recomendar um conjunto
reduzido de soluções, conduzir a contratação e acompanhar o primeiro uso.

A jornada deve parecer única para empregado e cliente, ainda que cadastro,
assinatura, contrato e ativação permaneçam sob responsabilidade de sistemas
corporativos existentes.

## 2. Objetivos

- Ampliar a superfície comercial para além da equipe oficialmente dedicada a
  vendas.
- Tornar o portfólio compreensível para empregados sem formação comercial.
- Reduzir etapas e redigitação entre prospecção, contratação e pós-venda.
- Preservar autoria e evidências sem bloquear a venda por disputas internas.
- Demonstrar, com dados, o potencial de um futuro modelo de reconhecimento.

## 3. Fora do escopo

- Formação de preços, descontos ou condições financeiras.
- Cálculo ou pagamento de comissão, gratificação ou folha.
- Criação de política trabalhista ou interpretação de direito remuneratório.
- Controle de jornada ou avaliação funcional do empregado.
- Substituição automática dos sistemas oficiais de contrato e assinatura.
- Atendimento empresarial para pessoa física sem CNPJ.

## 4. Atores

### Vendedor Eventual

Qualquer empregado ativo que adere voluntariamente, conclui a capacitação e
recebe permissão para os produtos de uma campanha.

### Vendedor especializado

Profissional que pode orientar, colaborar ou assumir a condução sem apagar a
autoria do originador.

### Cliente

Pessoa jurídica com CNPJ válido, incluindo MEI, representada por pessoa
autorizada.

### Coordenador ou administrador de carteira

Analisa solicitações de inclusão, duplicidades e conflitos de autoria.

### Administrador de campanha

Publica produtos, perguntas, treinamento, período e regras do piloto.

### Sistemas corporativos

Fontes oficiais de identidade funcional, cadastro, contratação, assinatura,
ativação e primeiro uso, conforme integração disponível.

## 5. Premissas

- O login do empregado ocorre apenas na rede dos Correios ou por acesso externo
  oficialmente autorizado.
- O cliente utiliza portal externo público e segregado, por link temporário.
- O piloto usa até três soluções: encomendas para pequenos negócios, coleta e
  logística reversa.
- A gratificação é apenas simulada no piloto e a funcionalidade remuneratória
  permanece desabilitada por padrão.
- O cadastro público por CNPJ é sugestão e precisa de confirmação do cliente.

### 5.1 Decisões da fundação de identidade e acesso

- Autenticação e autorização são dimensões separadas: qualquer empregado ativo
  reconhecido pelo provedor configurado pode autenticar, mas isso não concede
  acesso automático às aplicações do SPIV.
- Perfis Shield existentes continuam representando funções estáveis. Acesso
  temporário ou condicionado a campanha é concedido por capacidades com escopo,
  origem, início, término e trilha de auditoria.
- Uma concessão pode ser administrativa ou originada por campanha. Concessões de
  campanha deixam de ser efetivas quando a campanha termina, é suspensa ou sai
  de sua vigência, sem apagar o histórico.
- Suspensão administrativa e situação funcional inativa prevalecem sobre qualquer
  concessão. Uma concessão administrativa não prolonga silenciosamente uma
  campanha encerrada.
- O piloto fora da rede corporativa usa somente identidades fictícias, marcadas
  com origem `demo`. O modo demonstrativo e sua senha compartilhada precisam ser
  habilitados explicitamente por configuração e ficam desabilitados por padrão.
- Na rede corporativa, o LDAP será o provedor de identidade funcional. Ambos os
  modos alimentam o mesmo cadastro de empregados e as mesmas regras de
  autorização; nenhum dado real é necessário no piloto externo.
- A feature Vendedor Eventual permanece desabilitada por padrão até a validação
  integral das migrations, autorização, isolamento e implantação.

## 6. Jornadas

### J1 — Adesão

1. Empregado escolhe “Quero ser Vendedor Eventual”.
2. Consulta regras e produtos da campanha.
3. Conclui microtreinamento e avaliação.
4. Aceita os termos versionados.
5. Recebe habilitação imediata para o catálogo publicado.
6. Pode pausar ou encerrar a participação.

### J2 — Registro rápido

1. Empregado informa CNPJ ou contato mínimo.
2. Registra necessidade percebida e consentimento inicial.
3. SPIV cria oportunidade, identificador imutável e solicitação provisória de
   inclusão em carteira.
4. Empregado continua depois, envia link ao cliente ou pede apoio.

Meta de experiência: conclusão em até um minuto, excluído o tempo do cliente.

### J3 — Venda assistida

1. SPIV consulta dados pelo CNPJ.
2. Cliente confirma os dados sugeridos.
3. Empregado aplica questionário curto.
4. Sistema recomenda até três soluções e explica o motivo.
5. Empregado registra o que apresentou e o interesse manifestado.
6. Cliente completa dados e documentos presencialmente ou pelo portal externo.
7. Representante autorizado assina pelo meio oficial.
8. SPIV acompanha registro, ativação e primeiro uso.

### J4 — Apoio ou transferência

1. Originador escolhe pedir orientação, convidar colaborador ou transferir.
2. Especialista complementa diagnóstico ou assume o atendimento.
3. Linha do tempo distingue originador, condutor e colaboradores.
4. Transferência operacional não muda autoria automaticamente.

### J5 — Inclusão em carteira paralela à venda

1. Primeira oportunidade válida cria reserva provisória.
2. Contratação prossegue sem aguardar decisão.
3. Duplicidades e contratos existentes são sinalizados internamente.
4. Administrador decide com base nos logs.
5. O cliente nunca vê a disputa nem sofre bloqueio por ela.

### J6 — Pós-venda

1. Confirmar registro e ativação.
2. Orientar primeiro acesso.
3. Confirmar primeiro uso ou postagem.
4. Registrar e encaminhar dúvidas.
5. Acompanhar a resolução.
6. Coletar satisfação.
7. Identificar nova oportunidade.

## 7. Requisitos funcionais

### Identidade, acesso e adesão

- **RF-001:** permitir adesão voluntária a qualquer empregado ativo.
- **RF-002:** exigir capacitação e aceite versionados antes da habilitação.
- **RF-003:** permitir pausa, encerramento e suspensão administrativa sem apagar
  histórico.
- **RF-004:** restringir login à rede corporativa ou mecanismo remoto
  oficialmente habilitado.
- **RF-005:** suportar navegador móvel, desktop e instalação como aplicação web.

### Campanha, treinamento e catálogo

- **RF-006:** administrar campanhas por período, produtos, treinamento e regras.
- **RF-007:** publicar catálogo reduzido por campanha, região ou permissão.
- **RF-008:** cadastrar problema resolvido, perfil indicado, benefícios,
  restrições, documentos, roteiro e perguntas frequentes de cada produto.
- **RF-009:** versionar treinamento, catálogo, diagnóstico e termos.
- **RF-010:** não exibir meta obrigatória ou cobrança automática por inatividade.

### Diagnóstico e recomendação

- **RF-011:** oferecer questionário guiado com perguntas curtas e ramificações.
- **RF-012:** recomendar no máximo três soluções e explicar cada recomendação.
- **RF-013:** distinguir produto sugerido, apresentado e escolhido pelo cliente.
- **RF-014:** oferecer assistência conversacional baseada somente em conteúdo
  aprovado.
- **RF-015:** impedir que o assistente invente preço, condição, produto ou
  garantia.

### Oportunidade, autoria e carteira

- **RF-016:** gerar identificador imutável no primeiro registro, inclusive
  offline.
- **RF-017:** registrar originador, data, contexto, unidade, campanha e canal.
- **RF-018:** criar solicitação de inclusão sem bloquear a contratação.
- **RF-019:** manter carteira, autoria e contrato como estados independentes.
- **RF-020:** manter logs imutáveis; correções geram nova versão e justificativa.
- **RF-021:** detectar duplicidades e reuni-las para análise sem expor conflito ao
  cliente.
- **RF-022:** aceitar solicitação sobre contrato feito pela internet quando não
  houver autoria conhecida.

### Contratação

- **RF-023:** permitir preenchimento presencial assistido ou link para conclusão
  remota.
- **RF-024:** preencher dados públicos pelo CNPJ e exigir confirmação do cliente.
- **RF-025:** manter checklist documental dinâmico por natureza jurídica e
  produto.
- **RF-026:** permitir envio de documentos pelo cliente ou captura assistida.
- **RF-027:** usar apenas modalidades oficiais de assinatura.
- **RF-028:** impedir assinatura pelo empregado em nome do cliente.
- **RF-029:** encaminhar aos sistemas oficiais preservando o identificador da
  oportunidade.
- **RF-030:** acompanhar geração, assinatura, registro, ativação e primeiro uso.

### Consentimento e comunicação

- **RF-031:** aplicar consentimento progressivo para interesse, contato e
  contratação.
- **RF-032:** confirmar consentimento por QR Code, SMS, e-mail ou dispositivo
  presencial.
- **RF-033:** registrar finalidade, texto, versão, canal, data e resultado.
- **RF-034:** usar somente canais institucionais; nunca expor contato pessoal do
  empregado.
- **RF-035:** oferecer portal externo segregado, com token temporário e escopo
  mínimo.

### Offline

- **RF-036:** disponibilizar catálogo, diagnóstico e registro mínimo offline.
- **RF-037:** preservar horário do evento e registrar separadamente a
  sincronização.
- **RF-038:** verificar identidade, habilitação, carteira e duplicidade ao
  sincronizar.
- **RF-039:** eliminar dados temporários após sincronização ou expiração.
- **RF-040:** impedir conclusão contratual sem conexão autorizada.

### Colaboração e pós-venda

- **RF-041:** permitir orientação, colaboração ou transferência integral.
- **RF-042:** distinguir originador, condutor, colaborador, especialista,
  responsável de carteira e pós-venda.
- **RF-043:** gerar jornada automática de ativação e primeiro uso.
- **RF-044:** encaminhar problemas a áreas especializadas e acompanhar seu estado.

### Reconhecimento e métricas

- **RF-045:** oferecer campanha demonstrativa sem gerar direito financeiro.
- **RF-046:** registrar marcos de contrato ativado e primeiro uso como critérios
  demonstrativos iniciais.
- **RF-047:** exibir painel pessoal sem dados indevidos de outros empregados.
- **RF-048:** manter ranking e gamificação desabilitados, salvo configuração.
- **RF-049:** medir o funil da adesão ao pós-venda e os motivos de abandono.
- **RF-050:** exportar evidências para análise externa sem calcular pagamento.

## 8. Requisitos não funcionais

- **RNF-001 — Segurança:** autenticação corporativa, autorização por função e
  campanha, criptografia em trânsito e repouso e trilha de auditoria.
- **RNF-002 — Privacidade:** minimização, retenção configurável, acesso por
  necessidade e segregação entre portal interno e externo.
- **RNF-003 — Usabilidade:** tarefas essenciais utilizáveis em celular, linguagem
  simples e registro rápido em até um minuto.
- **RNF-004 — Acessibilidade:** compatibilidade com teclado, leitores de tela,
  contraste e ampliação conforme padrão corporativo aplicável.
- **RNF-005 — Resiliência:** operações offline idempotentes e retomada segura de
  jornadas interrompidas.
- **RNF-006 — Auditabilidade:** eventos críticos não destrutivos, com usuário,
  instante, origem, versão e correlação.
- **RNF-007 — Explicabilidade:** recomendações devem mostrar perguntas e regras
  relevantes para o resultado.
- **RNF-008 — Observabilidade:** erros de integração, filas, sincronização e
  reconciliação devem ser monitoráveis.
- **RNF-009 — Interoperabilidade:** integrações REST/JSON quando disponíveis e
  contingência por transição autenticada ou fluxo assistido.

## 9. Diagnóstico inicial

1. A empresa vende ou envia produtos?
2. Quantos envios realiza aproximadamente?
3. Costuma levar as encomendas até uma agência?
4. Gostaria de coleta no endereço?
5. Os clientes solicitam trocas ou devoluções?
6. Já possui contrato com os Correios?
7. Deseja conhecer uma solução adequada?

Regras iniciais:

- necessidade de envio sugere encomendas;
- volume recorrente ou dificuldade de deslocamento sugere coleta;
- trocas e devoluções sugerem logística reversa;
- contrato existente abre expansão ou pós-venda;
- ausência de contrato abre nova contratação.

As regras são hipóteses do piloto e devem ser validadas pelos donos do portfólio.

## 10. Critérios de aceite principais

### CA-01 — Adesão universal

**Dado** um empregado ativo autenticado em rede autorizada, **quando** concluir o
treinamento e aceitar os termos, **então** deve receber o papel de Vendedor
Eventual para o catálogo da campanha, independentemente de cargo.

### CA-02 — Registro sem atrito

**Dado** um Vendedor Eventual habilitado, **quando** registrar os dados mínimos,
**então** o sistema deve criar oportunidade, identificador e solicitação
provisória sem exigir decisão administrativa prévia.

### CA-03 — Conflito não bloqueia cliente

**Dado** um CNPJ com outra carteira ou oportunidade, **quando** houver novo
atendimento, **então** o sistema deve permitir continuar a contratação e enviar o
conflito para análise interna.

### CA-04 — Autoria preservada

**Dado** que um especialista assume a venda, **quando** a transferência for
concluída, **então** originador e novo condutor devem permanecer distintos na
linha do tempo.

### CA-05 — Contratação remota

**Dado** um cliente com consentimento de contato, **quando** receber o link,
**então** deve acessar apenas sua jornada, completar dados e prosseguir para a
assinatura oficial sem entrar na rede interna.

### CA-06 — Operação offline

**Dado** um empregado previamente autenticado e uma sessão offline válida,
**quando** registrar uma oportunidade sem rede, **então** o evento deve ser salvo
de forma protegida e sincronizado uma única vez na reconexão.

### CA-07 — Reconhecimento demonstrativo

**Dado** um contrato ativado com primeiro uso confirmado, **quando** a campanha
for demonstrativa, **então** o painel deve mostrar critério técnico atingido e o
aviso de que não existe direito financeiro.

### CA-08 — Recomendação controlada

**Dado** um conjunto de respostas, **quando** o assistente recomendar produtos,
**então** deve limitar-se ao catálogo publicado, mostrar o motivo e não criar
condições inexistentes.

## 11. Pendências de validação

- APIs e mecanismos de autenticação disponíveis nos sistemas corporativos.
- Forma aceita para propagar o identificador de correlação.
- Fonte oficial para ativação e primeiro uso.
- Textos de consentimento, privacidade e retenção.
- Documentos e assinatura exigidos por natureza jurídica e produto.
- Regras oficiais e nomes comerciais dos três produtos iniciais.
- Duração da sessão offline e requisitos para dispositivos pessoais.
- Autoridade responsável por conflitos de carteira no piloto.
- Unidades e cronograma de disponibilização, caso o piloto não seja corporativo.
