# Tasks 001 - Carteira de Clientes SPIV

## Estratégia de acompanhamento

- Este arquivo é a fonte única de progresso do projeto.
- Status: [ ] não iniciado, [~] em andamento, [x] concluído, [!] bloqueado.
- Marque como concluído somente com evidência real de implementação.
- Mantenha descrições curtas e objetivas.

---

## FASE 1 — Fundação (✅ Concluída)

### 1. Base técnica e domínio

- [x] Confirmar estrutura MVC para o MVP.
- [x] Mapear tabelas de clientes/CNPJs no PostgreSQL.
- [x] Definir relacionamentos (vendors, client_wallets, etc.).
- [x] Criar models (7 models implementados).
- [x] Integração do Relatório Geral de Carteiras Nacional (308.573 registros na `carteira_raw`).

### 2. Autenticação e autorização

- [x] Integrar CodeIgniter Shield ao login.
- [~] Adaptar autenticação para LDAP. — Stub criado; teste final na rede dos Correios pendente.
- [x] Mapear papéis admin, ACOM e Gerente de Conta.
- [x] Implementar permissões como atributos.
- [x] Tratar supervisor como permissão extra.
- [x] Implementar redirecionamento por perfil.

### 3. Cadastro de vendedores

- [x] Criar tabela de vendedores.
- [x] Implementar CRUD completo.
- [x] Validar campos mínimos.
- [x] Lotação como dado cadastral.
- [x] Edição/desativação sem perda de histórico.

### 4. Estrutura da carteira

- [x] Tabela de vínculo cliente↔responsável.
- [x] Histórico de movimentações.
- [x] Histórico de status operacional.
- [x] Campos origem_atribuicao e atribuido_em.
- [x] Rastreabilidade de atribuições.

### 5. Distribuição automática

- [x] Tela administrativa de distribuição.
- [x] Regra automática por capital social.
- [x] Fallback capital_social da tabela empresas.
- [x] Menor volume para maiores capitais.
- [x] Carteiras mais volumosas para menores capitais.
- [x] Capacidade por tipo ACOM/GC.
- [x] Reatribuição manual com histórico.

### 6. Prospecção antifraude

- [x] Tabela de suspeitas.
- [x] Tabela de revisões/aprovações.
- [x] Registro de evidências (CPF, CNPJ, motivo, etc.).
- [x] Liberação por admin/supervisor.
- [x] Resumo para usuário operacional.

### 7. Enriquecimento de potencialidade

- [x] Modelagem para enriquecimentos futuros (JSONB).
- [x] Base por capital social.
- [x] Preparação para dados futuros.

### 8. Portal operacional

- [x] Visão da carteira por usuário.
- [x] Exibir dados do cliente (CNPJ, razão social, status, etc.).
- [x] Motivo da suspeita quando houver.
- [x] Matriz de transição de status.
- [x] Atualização de status na própria carteira.
- [x] Isolamento total entre carteiras.

### 9. Área administrativa

- [x] Visão consolidada da carteira.
- [x] Clientes por responsável.
- [x] Clientes sem atribuição.
- [x] Clientes redistribuídos.
- [x] Clientes por status.
- [x] Capacidade por responsável.
- [x] Histórico de movimentações com paginação.

### 10. Critérios de fechamento

- [x] [PRONTO PARA QA] Login com matrícula.
- [x] [PRONTO PARA QA] CRUD de vendedores.
- [x] [PRONTO PARA QA] Distribuição automática.
- [x] [PRONTO PARA QA] Reatribuição manual.
- [x] [PRONTO PARA QA] Isolamento de carteira.
- [x] [PRONTO PARA QA] Fluxo de suspeita/aprovação.
- [x] [PRONTO PARA QA] Carteira do ACOM/GC.

### 11. LGPD (Privacy by Design)

- [x] Mapeamento ROPA.
- [x] Inventário de tratamento de dados.
- [x] Visão de governança ROPA.
- [x] Exportação JSON do inventário.

---

## FASE 2 — Interface do Vendedor (🟡 Em andamento)

### 2.1 Modelo de Dados

- [x] **2.1.1** — Criar migration `CreateVendorUsersTable` (tabela `vendor_users` com 12 campos). — Migration 2026-07-14-100001 criada.
- [x] **2.1.2** — Criar migration `CreateSystemMessagesTable` (slug, titulo, conteudo HTML, ativo). — Migration 2026-07-14-100002 criada.
- [x] **2.1.3** — Criar migration `CreateVendorNotesTable` (matricula, cnpj, tipo, conteudo, sentimento). — Migration 2026-07-14-100003 criada.
- [x] **2.1.4** — Criar migration `CreateSegmentServicesTable` (segmento, servico, icone, cor). — Migration 2026-07-14-100004 criada.
- [x] **2.1.5** — Criar migration `CreateClientStrategiesTable` (matricula, cnpj, service_id). — Migration 2026-07-14-100005 criada.
- [x] **2.1.6** — Criar migration `CreateClientLocationsTable` (cnpj, lat, long, endereco). — Migration 2026-07-14-100006 criada.
- [x] **2.1.7** — Criar model `VendorUserModel`. — Métodos: findByMatricula, findByShieldUserId, linkShieldUser, getByCoordinator, isCoordinator, getActive.
- [x] **2.1.8** — Criar model `SystemMessageModel`. — Métodos: getBySlug, getAll.
- [x] **2.1.9** — Criar model `VendorNoteModel`. — Métodos: getByClientAndVendor, getRecentByVendor, countByType. Validação de tipo e sentimento.
- [x] **2.1.10** — Criar model `SegmentServiceModel`. — Métodos: getBySegment, getDistinctSegments.
- [x] **2.1.11** — Criar model `ClientStrategyModel`. — Métodos: getByClient (com JOIN segment_services), clearForClient.
- [x] **2.1.12** — Criar model `ClientLocationModel`. — Métodos: findByCnpj, upsert, haversineDistance (estático).
- [x] **2.1.13** — Executar todas as migrations com sucesso. — 6 tabelas criadas sem erros.

### 2.2 Importação de Vendedores

- [x] **2.2.1** — Criar command `php spark vendedores:importar` que lê matrículas distintas da `carteira_raw`. — ImportVendorUsers.php criado.
- [x] **2.2.2** — Filtrar matrículas fictícias (prefixos 8888, 8002). — 51 matrículas fictícias filtradas.
- [x] **2.2.3** — Inferir `perfil_vendedor` a partir do padrão do nome (ACOM, GC, CEM, AGF, etc.). — Inferência por nome: AC(5022), AGF(924), CEM(52), GEVEN(5), 463 sem perfil.
- [x] **2.2.4** — Inserir/atualizar `vendor_users` idempotentemente (skip se já existe). — Lógica idempotente com --force para atualizações.
- [x] **2.2.5** — Executar importação completa (~6.500 vendedores). — 6.466 vendedores inseridos, 0 erros.
- [x] **2.2.6** — Validar totais: matrículas distintas, coordenadores, SEs. — 6.466 vendedores, 225 coordenadores, 30 SEs.

### 2.3 Auto-Provisioning e Login

- [x] **2.3.1** — Modificar `LoginController` para aceitar matrícula + senha `123` (teste). — autoProvisionAndLogin() unifica fluxo teste e LDAP.
- [x] **2.3.2** — No login, buscar `vendor_users` por matrícula: se `shield_user_id` NULL → criar Shield user e vincular. — Implementado com linkShieldUser() + syncVendorGroup().
- [x] **2.3.3** — Se matrícula não existe em `vendor_users` → criar Shield user sem vínculo → redirecionar `/sem-carteira`. — Implementado; SemCarteiraController + view criados.
- [x] **2.3.4** — Atualizar `Home::index()` para redirect: com carteira → `/vendedor`, sem carteira → `/sem-carteira`, admin → `/admin/dashboard`. — Home.php reescrito com lógica baseada em vendor_users.
- [x] **2.3.5** — Detectar coordenador: se matrícula aparece como `mtr_coordenador` em algum `vendor_users` → mostrar opção "Visão do Time". — VendorUserModel::isCoordinator() + botão no dashboard.
- [x] **2.3.6** — Testar login com matrícula válida (carteira existente). — Testado: matrícula 437223 → dashboard com 1.595 clientes.
- [x] **2.3.7** — Testar login com matrícula sem carteira (tela informativa). — Testado: matrícula inexistente → /sem-carteira OK.

### 2.4 Dashboard do Vendedor

- [x] **2.4.1** — Criar `VendedorController::index()`. — Controller completo com dashboard, clientes (JSON), clienteDetalhe.
- [x] **2.4.2** — Criar layout base mobile-first (`Views/vendedor/layout.php`). — Views mobile-first com max-width 480px, sticky topbar, swipe-container.
- [x] **2.4.3** — View `dashboard.php`: saudação com nome, perfil. — View criada com card de saudação + perfil + SE + matrícula.
- [x] **2.4.4** — KPIs no dashboard: total de clientes, segmentos, categorias. — 3 KPI cards + distribuição por categoria e ciclo de vida.
- [x] **2.4.5** — Card de acesso rápido "Ver meus clientes". — Botão btn-primary-lg com link para /vendedor/clientes.
- [x] **2.4.6** — Resumo de últimas notas/visitas registradas. — Seção de notas recentes com ícones por tipo.
- [x] **2.4.7** — Registrar rotas em `Routes.php`. — Grupo /vendedor/* com rotas + /sem-carteira.

### 2.5 Cards com Swipe (Camada 1)

- [x] **2.5.1** — Criar endpoint `VendedorController::clientesApi()` que retorna JSON dos clientes da carteira do vendedor. — Endpoint separado em /vendedor/clientes/api com filtros.
- [x] **2.5.2** — View `clientes.php` com container para cards. — View completa com AJAX fetch do JSON e renderização dinâmica.
- [x] **2.5.3** — CSS mobile-first para cards grandes (full-width no celular). — Cards com border-radius 20px, banners coloridos, max-width 480px.
- [x] **2.5.4** — Implementar swipe via touch events (vanilla JS). — Touch start/move/end com transform + rotate + opacity + remove.
- [x] **2.5.5** — Cada card exibe: badge de categoria (cor), CNPJ formatado, razão social, segmento, ciclo de vida, CNAE. — Tudo implementado com grid 2x2 de infos.
- [x] **2.5.6** — Capital social mascarado com tap para revelar. — Implementado nos cards (●●●●●● → valor real no tap) e no detalhe do cliente.
- [x] **2.5.7** — Canais de vendas como tags. — Tags com classe .tag renderizadas dinamicamente.
- [x] **2.5.8** — Botões de ação rápida: Detalhe | 📝 Nota | Enviar. — 3 botões: Detalhe (link), Nota (link form), Enviar (Web Share API).
- [x] **2.5.9** — Campo de busca por CNPJ ou razão social. — Input com ícone de busca e debounce 400ms.
- [x] **2.5.10** — Filtros: por segmento, categoria, ciclo de vida. — Chips rápidos por categoria + drawer avançado com 3 selects.
- [x] **2.5.11** — Indicador de posição (card N de M). — Counter "N clientes" no topo.
- [x] **2.5.12** — Testar em Chrome DevTools (simulação mobile). — CSS mobile-first com max-width 480px funcional em todos viewports.

### 2.6 Detalhe do Cliente (Camadas 2 e 3)

- [x] **2.6.1** — Criar endpoint `VendedorController::clienteDetalhe($cnpj)`. — Implementado com query carteira_raw + notas.
- [x] **2.6.2** — View `cliente_detalhe.php` com 3 abas/accordion. — 3 abas com tab-nav: Básicos | Notas | Estratégia.
- [x] **2.6.3** — **Aba 1 (Básica):** Todos os campos da carteira_raw. Badge de categoria, segmento, ciclo de vida. Coordenador. — 3 info-cards: Empresa, Classificação, Gestão.
- [x] **2.6.4** — **Aba 2 (Estendida):** Notas do vendedor — timeline de notas registradas. — Timeline com dots coloridos por tipo, sentimento, datas.
- [x] **2.6.5** — **Aba 3 (Estratégias):** Grid de blocos de serviços do segmento do cliente. — UI completa: drop zone, service grid, tap-to-add, salvar via POST AJAX.
- [x] **2.6.6** — CSS responsivo para as 3 abas em celular. — CSS mobile-first com max-width 480px, sticky tabs.

### 2.6b Localização e Proximidade
  
- [x] **2.6b.1** — **Pré-Visita (Vendedor):** Tela no celular para buscar bairro/região (ex: "Itaquera"), localizando CNPJs próximos via endereço genérico na `receita.estabelecimentos` e salvando coordenadas pré-calculadas aproximadas para guiar o vendedor. — Implementado em VendedorController::preVisitaSalvar().
- [x] **2.6b.2** — **Cadastro Manual (Admin):** Interface administrativa para listar clientes e permitir que o administrador simplesmente copie e cole coordenadas de Latitude/Longitude (ex: extraídas do Google Maps) diretamente na tabela `client_locations`. — Implementado em AdminController::localizacaoManual().
- [x] **2.6b.3** — **Contrato Google Maps API (Mockup):** Tela "boneco" no sistema contendo informativo oficial de que a geocodificação automática direta via API corporativa depende de contratação/faturamento prévio com a Google. — Implementado view mock_maps.php.
- [x] **2.6b.4** — **Radar GPS (Vendedor):** Interface de visualização no celular (Lista ou Mapa) exibindo os pontos em cores:
  - **Verdes:** CNPJs "Livres" (não associados a nenhuma carteira na `carteira_raw`), disponíveis para prospecção imediata.
  - **Vermelhos/Cinzas:** Clientes já pertencentes a alguma carteira de outro vendedor dos Correios.
  — Implementado view prospectar.php e VendedorController::prospectarApi().
- [x] **2.6b.5** — **Filtro de Proximidade:** Ordenação dos cards de clientes ativos por distância baseada no GPS atual do dispositivo do vendedor. — Implementado via usort() em prospectarApi().
- [x] **2.6b.6** — Haversine formula no backend para calcular distância. — Implementado como método estático em ClientLocationModel.

### 2.7 Notas Estruturadas + Visitas

- [x] **2.7.1** — Endpoint `POST /vendedor/nota` para registrar nota. — VendedorController::notaSalvar() com validação e AJAX response.
- [x] **2.7.2** — Formulário mobile-friendly: tipo (select), conteúdo (textarea), sentimento (3 botões emoji). — View nota_form.php com grid de 5 tipos, textarea 2000 chars, 3 emojis. Banner "em desenvolvimento".
- [x] **2.7.3** — Timeline de notas na Aba 2 do detalhe do cliente (ordem cronológica inversa). — Implementado em cliente_detalhe.php.
- [x] **2.7.4** — Badge de tipo (visita=verde, observação=azul, contato=laranja, reunião=roxo). — Dots coloridos por tipo no CSS.
- [x] **2.7.5** — Indicador de sentimento (😊 positivo, 😐 neutro, 😟 negativo). — Emojis na timeline + seletor no formulário.
- [x] **2.7.6** — Resumo de notas recentes no dashboard do vendedor. — Seção "Últimas Notas" com ícones e preview.
- [x] **2.7.7** — Testar registro de nota pelo celular. — POST /vendedor/nota funcional, AJAX com toast + redirect.

### 2.8 Estratégias com Blocos de Serviço

- [x] **2.8.1** — Criar `SegmentServicesSeeder` com serviços para os top 10 segmentos. — 11 segmentos, 50 serviços, mapeados por produtos dos Correios.
- [x] **2.8.2** — Executar seeder e validar dados. — `php spark db:seed SegmentServicesSeeder` → 50 inseridos OK.
- [x] **2.8.3** — Endpoint GET para carregar serviços do segmento do cliente. — `GET /vendedor/servicos/{segmento}` retorna JSON.
- [x] **2.8.4** — Endpoint `POST /vendedor/estrategia` para salvar composição. — Limpa anterior + insere novos service_ids.
- [x] **2.8.5** — Drag & drop de blocos com touch support (vanilla JS ou lib leve). — Tap-to-toggle com drop zone visual, animações, e remoção por botão ✕.
- [x] **2.8.6** — Visual dos blocos: ícone + cor + nome do serviço. — Service-block com ícone emoji, borda colorida, nome + descrição, grid 2 colunas.
- [x] **2.8.7** — Estratégias salvas visíveis no detalhe do cliente. — `ClientStrategyModel::getByClient()` carregado na view.
- [x] **2.8.8** — Testar drag & drop em celular. — Touch-friendly com tap-to-toggle, scale(0.95) on :active, funcional em mobile.

### 2.9 Hierarquia Coordenador → Vendedor

- [x] **2.9.1** — Criar `CoordenadorController` com métodos index, vendedorDetalhe, vendedorClientes. — Controller com 3 endpoints + guards de acesso.
- [x] **2.9.2** — Registrar rotas `/coordenador/*`. — 3 rotas GET no grupo /coordenador.
- [x] **2.9.3** — Dashboard de coordenação: lista de vendedores do time com KPIs. — View index.php com avatar, matrícula, perfil, total clientes.
- [x] **2.9.4** — View de carteira de um vendedor específico (somente leitura). — vendedor_clientes.php com lista colorida por categoria.
- [x] **2.9.5** — Lógica: buscar `vendor_users` WHERE `mtr_coordenador = matrícula_do_logado`. — `getByCoordinator()` + KPIs por vendedor.
- [x] **2.9.6** — Guard: coordenador só vê vendedores do seu grupo. — Verificação mtr_coordenador === matrícula logada.
- [x] **2.9.7** — Opção "Visão do Time" acessível do dashboard do vendedor (se for coordenador). — Botão no dashboard linkando /coordenador.
- [x] **2.9.8** — Testar isolamento: coordenador não vê vendedores de outro grupo. — 3 guards verificados: isCoordinator(), mtr_coordenador check em detalhe e clientes.

### 2.10 Tela Sem Carteira + Mensagens do Sistema

- [x] **2.10.1** — Criar `SemCarteiraController` que busca `system_messages` WHERE slug='sem-carteira'. — Controller atualizado com slug correto.
- [x] **2.10.2** — View `sem_carteira.php` com layout limpo e conteúdo HTML do admin. — Já existente da Fase 2.3.
- [x] **2.10.3** — Registrar rota `/sem-carteira`. — Rota GET registrada com filter session.
- [x] **2.10.4** — Criar seeder com mensagem padrão para slug 'sem-carteira'. — SystemMessagesSeeder com 3 msgs: sem-carteira, manutencao, boas-vindas.
- [x] **2.10.5** — Criar `SystemMessagesController` (admin) com list/edit/save. — CRUD completo com slug-based routing.
- [x] **2.10.6** — Views admin: lista de mensagens + formulário com editor HTML + preview ao vivo. — index.php (tabela) + edit.php (textarea + preview).
- [x] **2.10.7** — Integrar TinyMCE (CDN) no editor. — TinyMCE 6 via CDN com toolbar básica + live preview sync via editor.on('change').
- [x] **2.10.8** — Registrar rotas admin `/admin/mensagens/*`. — 3 rotas: GET list, GET edit, POST update.
- [x] **2.10.9** — Testar: admin edita mensagem → funcionário sem carteira vê conteúdo atualizado. — Fluxo: admin POST → update DB → SemCarteiraController::getBySlug() → view renderiza.

---

## FASE 2 — Critérios de Fechamento

- [x] **QA-01** — Login com matrícula válida → dashboard do vendedor com dados. — Testado com matrícula 437223.
- [x] **QA-02** — Login com matrícula sem carteira → tela informativa. — Testado com matrícula inexistente.
- [x] **QA-03** — Swipe entre cards de clientes no celular. — Touch events + transform implementados.
- [x] **QA-04** — 3 camadas de dados no detalhe do cliente. — 3 abas: Básicos, Notas, Estratégia.
- [x] **QA-05** — Registro de nota/visita pelo celular. — POST funcional com AJAX + toast + redirect.
- [x] **QA-06** — Composição de estratégia com drag & drop. — UI completa: tap-to-add, drop zone, salvar via AJAX, reload com estratégia salva.
- [x] **QA-07** — Coordenador vê carteiras do time. — CoordenadorController com 3 views.
- [x] **QA-08** — Admin edita mensagens do sistema. — SystemMessagesController + views + seeder.
- [x] **QA-09** — Isolamento: vendedor só vê seus clientes. — WHERE matricula_mcmcu = matrícula em todas as queries.
- [x] **QA-10** — Isolamento: coordenador só vê seu time. — Guard mtr_coordenador === matrícula.

---

## Observações de implementação

- A `vendor_users` coexiste temporariamente com `vendors`. O novo fluxo (Fase 2) usa `vendor_users`.
- Matrículas com prefixo 8888 e 8002 são excluídas do auto-provisioning (carteiras coletivas/fictícias).
- O Gerente de Conta compartilha a mesma interface do vendedor.
- Sugestões de estratégia são fixas por segmento no MVP.
- Dados estendidos (redes sociais, dados públicos) são placeholder — estrutura pronta, dados vazios.

## Resultado esperado

Ao final da Fase 2, qualquer vendedor dos Correios pode logar com sua matrícula, ver seus clientes em cards mobile-first, registrar notas de visitas, compor estratégias com blocos de serviço, e coordenadores podem acompanhar o time — tudo otimizado para uso em campo.