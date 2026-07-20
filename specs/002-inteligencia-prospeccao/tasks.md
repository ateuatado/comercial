# Tasks 002 - Checklist de Implementação de Inteligência Comercial (Fase 3)

Esta checklist organiza granularmente o desenvolvimento do módulo de **Scoring Preditivo e Inteligência** da Fase 3 do SPIV.

---

## 1. Estrutura do Banco de Dados e Migrations

- [x] Criar migration para a tabela `scoring_config`. — Migration `2026-07-19-000001_CreateScoringConfigTable` criada.
- [x] Criar migration para a tabela `cnae_scoring_rules`. — Migration `2026-07-19-000002_CreateCnaeScoringRulesTable` criada.
- [x] Criar migration para a tabela `client_enrichment`. — Migration `2026-07-19-000003_CreateClientEnrichmentTable` criada.
- [x] Criar Seeder `CnaeRulesSeeder` com regras para CNAEs de e-commerce/varejo. — 50+ CNAEs com pesos elevados.
- [x] Criar Seeder `ScoringConfigSeeder` com pesos iniciais. — weight_cnae=40, weight_capital=20, weight_email=15, weight_nome_fantasia=10, weight_localizacao=15, amortization=70.

## 2. Interface Administrativa de Parametrizador de Scores

- [x] Rota `/admin/scoring` implementada. — AdminController::scoringConfig().
- [x] View `scoring_config.php` com sliders, validação 100%, tabela CRUD de CNAEs e barra de progresso. — Implementada com JS em tempo real.
- [x] Painel Preditivo acessível via engrenagem no admin dashboard. — Link no gear dropdown.

## 3. Backend e Lógicas de API AJAX

- [x] Rota POST `/admin/scoring/salvar`. — AdminController::scoringSalvar().
- [x] Comando CLI `RecalculateScores.php` com query PostgreSQL CTE + unnest() + amortização. — App\Commands\RecalculateScores.
- [x] Escrita de progresso em cache a cada chunk de 5.000 registros. — Cache::save('scoring_recalculation_progress').
- [x] Rota POST `/admin/scoring/recalcular` dispara CLI em background. — proc_open() no AdminController.
- [x] Rota GET `/admin/scoring/progresso` retorna JSON com percentual. — AdminController::scoringProgresso().
- [x] Polling AJAX na view até 100%, oculta barra e recarrega. — JavaScript implementado em scoring_config.php.
- [x] CRUD de CNAEs via API: adicionar/remover regras individuais. — AdminController::cnaeAdicionar() e ::cnaeRemover().

## 4. Testes e Validação de Dados

- [ ] Criar testes unitários para CNAE varejista como principal.
- [ ] Criar testes unitários para CNAE varejista como secundário (amortizado).
- [ ] Validar e-mail institucional no score.

---

## 3.2 — Ranking Preditivo na Prospecção (Fase 3.2)

- [x] Aba "Ranking de Potencial" na tela de prospecção/pesquisa. — Tab em prospeccao_pesquisa.php.
- [x] Endpoint GET `/vendedor/prospectar/pesquisa/ranking` retorna leads ordenados por score DESC. — rankingApi() no VendedorController.
- [x] Cards de ranking com barra visual de score, CNAE, badge colorido. — Implementado com buildScoreTooltipHtml().
- [x] Tooltip com breakdown por fator (pts por CNAE, capital, email, etc.). — Tooltip expandível com mini-barras por fator.
- [x] Load more paginado (offset/limit). — Implementado com botão "Carregar mais".
- [x] Score badge nos cards da busca geral também. — Badge com score + classe de cor.

---

## 3.3 — Mapa da Carteira (Fase 3.3)

- [x] Tela `/vendedor/clientes/ver-mapa` com mapa Leaflet/OSM. — View `clientes_mapa.php`.
- [x] Camada azul: meus clientes (coloridos por score). — `clientesMapaApi()`.
- [x] Camada vermelha: CNPJs livres (sem carteira) com lat/lng. — Corrigido para: livres = sem carteira do próprio vendedor.
- [x] Camada laranja: CNPJs em carteira de outro vendedor mas já geocodificados. — Flag `ocupado` no `livresMapaApi()`.
- [x] Toggle independente por camada com contador. — 3 botões com layerState.
- [x] Bottom sheet ao clicar: nome, CNPJ, score, botão de ação contextual. — Sheet com pill colorido por tipo.
- [x] Botão "Mapa da Carteira" no dashboard do vendedor. — Adicionado na seção de ações rápidas.

---

## 3.4 — PR-CAP: Pedido de Captação de Clientes (Fase 3.4)

- [x] Remover auto-add de `clienteDetalhe()`. — Bloco de insert automático removido.
- [x] Criar migration `captacao_requests` com campos de declaração e fluxo administrativo. — 2026-07-20-200001.
- [x] Banner "Este cliente não está na sua carteira" + botão "Solicitar Adição" no detalhe. — Modo prospecto no cliente_detalhe.php.
- [x] View `captacao_form.php` com evidências do sistema pré-carregadas (geocod, RFB, redes, notas). — Formulário mobile-first.
- [x] Endpoint `captacaoSolicitar($cnpj)` e `captacaoSalvar()` no VendedorController. — POST gravado em captacao_requests.
- [x] View `minhas_captacoes.php` com lista de PR-CAPs e status colorido. — Badge por status + ações contextuais.
- [x] Botão "Minhas Solicitações de Captação" no dashboard do vendedor. — Adicionado.
- [x] Painel admin `/admin/captacoes` com abas por status e indicador de disputa. — AdminController::captacoesIndex().
- [x] Tela de decisão `/admin/captacoes/{id}` com evidências do sistema, score e 3 botões. — captacao_detalhe.php.
- [x] POST `/admin/captacoes/decisao`: aprovação insere/transfere em carteira_raw; rejeição e mais_info notificam vendedor. — captacaoDecisao().
- [x] Coordenador também acessa `/coordenador/captacoes`. — Rotas mapeadas + botão na tela do coordenador.
- [x] Link "Pedidos de Captação" no gear menu e Ações Rápidas do admin dashboard. — Adicionado.
