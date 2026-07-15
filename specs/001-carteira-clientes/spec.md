# Spec 001 - Carteira de Clientes SPIV

## Objetivo

Construir o SPIV — Sistema de Prospecção e Inteligência de Vendas — para gerenciar carteiras de clientes a partir de dados já importados no PostgreSQL, com interface mobile-first para vendedores e gestão hierárquica por coordenadores e administradores.

## Decisões de escopo aceitas para o MVP

- Critério inicial de balanceamento: uso do campo `capital_social` da tabela `empresas` como base principal para a distribuição automática.
- Status operacional: mantido por cliente na carteira, com histórico de transições.
- Distribuição automática inicial: com possibilidade de ajuste manual posterior pelo admin.
- Prospecção antifraude: aprovação por admin; o ACOM vê apenas um resumo da suspeita.
- Campos mínimos do vendedor: matrícula, nome, lotação, tipo de ACOM e estado/SE.
- Base de dados da carteira nacional (308.573 registros) importada na tabela `carteira_raw` como fonte de verdade para a operação existente.

## Decisões da Fase 2 (Interface do Vendedor)

Decisões registradas em 14/Jul/2026:

- **Auto-provisioning:** Quando uma matrícula entra no sistema pela primeira vez, o usuário Shield é criado automaticamente. No ambiente de testes, a senha é `123` para qualquer matrícula.
- **Vínculo por matrícula:** A tabela `vendor_users` é populada a partir das matrículas distintas da `carteira_raw`. Quem possui matrícula na `vendor_users` tem carteira; quem não possui, vê tela informativa.
- **Interface mobile-first:** Cards grandes com swipe, estilo Tinder, otimizados para uso em campo (visita ao cliente, reunião, trânsito).
- **3 camadas de dados do cliente:**
  - Camada 1 (Básica): CNPJ, razão social, categoria, segmento, ciclo de vida, CNAE, canais de vendas, conta comercial. Capital social mascarado (tap para revelar).
  - Camada 2 (Estendida/placeholder): Redes sociais (Instagram, Facebook, LinkedIn, Site), dados públicos (endereço, telefone, email, nº funcionários, data abertura), notas estruturadas do vendedor.
  - Camada 3 (Estratégias): Blocos de serviços por segmento de mercado que o vendedor arrasta para compor estratégias visuais por cliente (PAC, SEDEX, Logística Reversa, etc.).
- **Notas estruturadas:** Campo chave para retroalimentação e enriquecimento de dados, com tipo (visita, observação, contato, reunião, estratégia) e sentimento (positivo, neutro, negativo).
- **Sugestões de estratégia:** Fixas por segmento no MVP, dinâmicas e individuais no futuro.
- **Proximidade geográfica:** Interface para vendedor cadastrar lat/long dos clientes. Ordenação de carteira por proximidade.
- **Botões de ação rápida:** Ligar (discador) e Navegar (Google Maps).
- **Hierarquia:** Coordenadores veem as carteiras dos vendedores sob sua coordenação. Permite delegação de atividades de gestão.
- **Tela sem carteira:** Apenas informativa com conteúdo editável pelo admin via rich text.
- **Mensagens do sistema:** Tabela `system_messages` para mensagens editáveis pelo admin (sem carteira, avisos gerais).
- **Perfil de vendedor:** Campo `perfil_vendedor` substitui o rígido `tipo_acom`, suportando ACOM, GC, CEM, AGF, CAC e outros.

## Contexto

O sistema roda na rede interna dos Correios. A autenticação será feita com o CodeIgniter Shield integrado a LDAP, usando matrícula e senha.

A base de CNPJs da Receita Federal já está importada no banco de dados do SPIV. A tabela `carteira_raw` contém 308.573 registros do relatório geral de carteiras nacional, com 6.517 matrículas de vendedores distintas, 226 coordenadores e 30 superintendências.

## Personas

- **Admin do sistema:** administra vendedores, distribui carteiras, gerencia mensagens do sistema e acompanha a operação.
- **Vendedor (ACOM/GC/CEM/outros):** acessa sua carteira mobile-first, vê dados enriquecidos dos clientes, registra notas/visitas, compõe estratégias de venda.
- **Coordenador:** visualiza as carteiras dos vendedores sob sua coordenação; delega atividades de gestão.
- **Funcionário sem carteira:** acessa o sistema mas vê apenas tela informativa com orientações.

## Escopo funcional

### 1. Autenticação e perfis

- Login com matrícula e senha.
- Integração com LDAP para validar credenciais (rede Correios).
- Auto-provisioning: no primeiro login, o sistema cria automaticamente o usuário Shield e vincula à `vendor_users` se a matrícula existir.
- Ambiente de testes: qualquer matrícula entra com senha `123`.
- Separação de acesso: admin, vendedor (com carteira), coordenador, funcionário sem carteira.
- Permissões especiais devem ser tratadas como atributos controlados pelo admin, podendo ser adicionadas ou removidas sem mudar o perfil base do usuário.
- O atributo de supervisor deve ser tratado como permissão extra do admin, não como perfil separado no login.
- Redirecionamento conforme o perfil do usuário autenticado:
  - Com carteira → `/vendedor`
  - Coordenador → opção "Visão do Time"
  - Admin → `/admin/dashboard`
  - Sem carteira → `/sem-carteira`

### 2. Administração de vendedores

- CRUD completo de vendedores.
- Cadastro de dados básicos necessários para operação e distribuição.
- Campos: matrícula, nome, lotação, perfil de vendedor, estado/SE, coordenador.
- Edição e desativação de vendedores sem perda do histórico.
- Tabela `vendor_users` populada automaticamente da `carteira_raw` (6.517 matrículas).
- Para novos vendedores: admin cria registro em `vendor_users`, que habilita o login.

### 3. Distribuição de carteira

- Distribuição dos CNPJs/clientes para os vendedores com regra híbrida.
- A carteira inicial deve ser distribuída automaticamente por regra.
- O primeiro critério de balanceamento da distribuição automática deve considerar o capital social da empresa.
- O tipo/perfil do vendedor influencia a capacidade de atendimento.
- O admin pode ajustar manualmente a carteira quando necessário.
- Toda reatribuição manual deve manter histórico/auditoria.

### 3.1 Prospecção antifraude

- O sistema deve apoiar prospecções com CPF para identificar riscos de fraude.
- Casos suspeitos devem ser sinalizados e revisados por admin/supervisor.
- A sinalização deve registrar evidências completas.

### 3.2 Enriquecimento de potencialidade

- O sistema deve nascer preparado para enriquecer a potencialidade do cliente.
- A base atual usa capital social como referência inicial, com evolução futura para modelos mais ricos.

### 4. Portal do Vendedor (Mobile-First)

- Interface de cards grandes com swipe para navegar entre clientes.
- Dashboard com saudação, KPIs e atalhos rápidos.
- 3 camadas de dados por cliente:
  - **Camada 1 — Dados Básicos:** CNPJ, razão social, categoria (badge colorido), segmento, ciclo de vida, CNAE, canais de vendas, conta comercial. Capital social mascarado com tap para revelar.
  - **Camada 2 — Dados Estendidos (placeholder):** Redes sociais (Instagram, Facebook, LinkedIn, Site), dados públicos, notas estruturadas do vendedor com tipo e sentimento.
  - **Camada 3 — Estratégias:** Blocos de serviços por segmento (drag & drop). Composição visual de estratégias por cliente com apelo comercial.
- Filtros: por segmento, categoria, ciclo de vida.
- Ordenação: alfabética, proximidade (lat/long).
- Busca por CNPJ ou razão social.
- Botões de ação rápida: Ligar (discador), Navegar (Google Maps).
- Registro de visitas/interações pelo celular.
- Cadastro de localização (lat/long) dos clientes.

### 5. Área administrativa

- Tela para visualizar a carteira consolidada.
- Tela para disparar ou revisar distribuições.
- Visão do vínculo entre clientes e vendedores.
- Consulta do histórico de movimentações da carteira.
- Métricas: quantidade de clientes por vendedor, sem atribuição, redistribuídos, por status, capacidade.
- Editor de mensagens do sistema (rich text).

### 6. Status operacional do cliente

- Status operacionais: novo, em acompanhamento, convertido, sem contato, bloqueado, inativo.
- Histórico de transições com quem e quando.
- Matriz de transição por perfil (vendedor: operacionais; admin: bloqueio/inativação/reativação).

### 7. Hierarquia e Coordenação

- Coordenadores visualizam as carteiras dos vendedores sob sua coordenação.
- Vínculo coordenador→vendedor via campo `mtr_coordenador` na `vendor_users`.
- Dashboard de coordenação com visão do time, KPIs agregados.
- Delegação de atividades de gestão para coordenadores/gerentes.

### 8. Tela Sem Carteira + Mensagens do Sistema

- Funcionário sem carteira vê tela informativa com conteúdo editável pelo admin.
- Tabela `system_messages` com slug, título, conteúdo HTML rico, ativo.
- Admin edita mensagens via interface com editor rich text (TinyMCE ou similar).
- Mensagens disponíveis: sem_carteira, boas_vindas, manutenção, etc.

## Fora de escopo por enquanto

- Regras avançadas de scoring comercial por IA.
- Automação externa fora da rede interna.
- Integrações adicionais além de LDAP no momento inicial.
- Preenchimento real dos dados estendidos (Camada 2 — redes sociais, dados públicos).
- Sugestões dinâmicas de estratégia por cliente (MVP usa fixas por segmento).
- Controle de férias/afastamentos de vendedores.

## Regras de negócio

- Um cliente deve pertencer a um vendedor por vez, salvo se o histórico registrar mudanças.
- O vendedor só enxerga seus próprios clientes.
- O coordenador enxerga as carteiras dos vendedores sob sua coordenação.
- O admin pode reatribuir clientes manualmente (auditável).
- Distribuição automática e manual coexistem sem perder rastreabilidade.
- Auto-provisioning: matrícula presente em `vendor_users` → acesso à carteira; ausente → tela sem carteira.
- Notas do vendedor são chave para retroalimentação e enriquecimento de dados.
- Estratégias são compostas por blocos de serviço por segmento.

## Requisitos de experiência

- Mobile-first: interface otimizada para celular em campo.
- Cards grandes com swipe para navegação entre clientes.
- Cores e ícones contextuais para marcar onde o usuário está e qual o objetivo.
- Informações facilmente acessíveis, sem demora.
- Capital social mascarado por privacidade (tap para revelar).
- Categoria institucional como badge colorido proeminente.

## Critérios de aceite

- O usuário consegue logar com matrícula e senha (auto-provisioning no 1º acesso).
- Um vendedor com matrícula na `vendor_users` vê seus clientes em cards.
- Um funcionário sem matrícula em `vendor_users` vê a tela informativa.
- Um coordenador vê as carteiras dos vendedores do seu time.
- O vendedor pode swipe entre clientes e acessar as 3 camadas de dados.
- O vendedor pode registrar notas/visitas pelo celular.
- O vendedor pode compor estratégias com blocos de serviço.
- O admin pode editar mensagens do sistema.
- O admin pode cadastrar, editar e desativar vendedores.
- O admin pode distribuir e reatribuir clientes com auditoria.

## Dependências

- CodeIgniter 4.
- CodeIgniter Shield.
- LDAP disponível na rede interna (ou senha `123` para testes).
- Banco PostgreSQL com base de CNPJs e `carteira_raw` importada.
- Chart.js (CDN) para gráficos eventuais.
- TinyMCE ou similar (CDN) para editor rich text.

## Perguntas em aberto

- ~~Quais campos mínimos do vendedor precisam existir além da matrícula e do nome?~~ Respondido: perfil, lotação, SE, coordenador.
- ~~A distribuição automática usa apenas balanceamento de volume ou também critérios adicionais?~~ Respondido: capital social + tipo/perfil.
- ~~A carteira terá status por cliente ou apenas vínculo simples com o ACOM?~~ Respondido: status + histórico.
- ~~Que métricas o admin precisa enxergar na tela de distribuição?~~ Respondido: KPIs implementados.
- Matrículas fictícias (8888*/8002*) devem ser excluídas do auto-provisioning? (Sugestão: sim)
- Tabela `vendors` existente será depreciada em favor de `vendor_users`?
