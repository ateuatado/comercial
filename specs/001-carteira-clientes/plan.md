# Plan 001 - Carteira de Clientes SPIV

## Objetivo técnico

Implementar o SPIV usando CodeIgniter 4, Shield, LDAP e PostgreSQL, evoluindo de uma base administrativa (Fase 1 — concluída) para uma plataforma mobile-first para vendedores com enriquecimento de dados, hierarquia de coordenação e gestão de estratégias comerciais.

## Fases de implementação

### Fase 1 — Fundação (✅ Concluída)

- Autenticação Shield + LDAP stub
- CRUD de vendedores
- Distribuição automática por capital social
- Portal operacional do ACOM (tabela)
- Área administrativa com métricas
- Prospecção antifraude
- Enriquecimento de potencialidade (modelo)
- LGPD/ROPA
- Importação de 308.573 registros na `carteira_raw`

### Fase 2 — Interface do Vendedor (🟡 Em planejamento)

Organizada em 10 sub-fases sequenciais:

- **2.1** — Modelo de dados (migrations + models)
- **2.2** — Importação de vendedores da carteira_raw
- **2.3** — Auto-provisioning (login por matrícula)
- **2.4** — Dashboard do vendedor
- **2.5** — Cards com swipe (Camada 1)
- **2.6** — Detalhe do cliente (Camadas 2 e 3)
- **2.6b** — Localização e proximidade
- **2.7** — Notas estruturadas + registro de visitas
- **2.8** — Estratégias com blocos de serviço
- **2.9** — Hierarquia coordenador → vendedor
- **2.10** — Tela sem carteira + mensagens do sistema

## Dependências entre fases

```
Fase 2.1 (Migrations) → 2.2 (Import) → 2.3 (Login) → 2.4 (Dashboard)
                                                        ↓
                                            2.5 (Cards/Swipe) → 2.6 (Detalhe)
                                                        ↓           ↓
                                                   2.6b (Geo)   2.7 (Notas)
                                                                    ↓
                                                                2.8 (Estratégias)
                                                                    ↓
                                                                2.9 (Hierarquia)
                                                                    ↓
                                                               2.10 (Sem carteira)
```

## Premissas

- A base de CNPJs da Receita Federal já existe no PostgreSQL.
- A `carteira_raw` contém 308.573 registros com 6.517 matrículas distintas.
- A autenticação usa matrícula + senha (LDAP em produção, `123` em testes).
- O MVP mobile-first deve atender vendedores em campo (celular).
- Coordenadores precisam de visão gerencial do time.

## Decisões de arquitetura

### 1. Framework e camadas

- CodeIgniter 4 como base, padrão MVC sem abstrações desnecessárias.
- Controllers finos; regras de negócio em models e services.
- Frontend mobile-first com Bootstrap 5 + JS vanilla para swipe.

### 2. Autenticação

- Shield como camada de autenticação e autorização.
- Auto-provisioning: primeiro login cria usuário Shield automaticamente.
- Vinculação Shield ↔ vendor_users por matrícula.
- Sem matrícula em vendor_users → tela "sem carteira".

### 3. Persistência

- PostgreSQL como banco principal.
- `carteira_raw` como fonte de verdade para a operação existente.
- `vendor_users` populada da carteira_raw (substituirá `vendors` gradualmente).
- Novas tabelas: vendor_notes, segment_services, client_strategies, client_locations, system_messages.

### 4. Interface do Vendedor

- Cards grandes com swipe (navegação por toque).
- 3 camadas de dados em abas/accordion no detalhe do cliente.
- Botões de ação rápida (Ligar, Navegar).
- Notas estruturadas com tipo e sentimento.
- Blocos de serviço por segmento (drag & drop).
- Cadastro de lat/long para ordenação por proximidade.

### 5. Hierarquia

- Coordenador vê vendedores WHERE mtr_coordenador = matrícula do logado.
- Dashboard de coordenação com KPIs do time.
- Não é admin completo — escopo limitado ao próprio grupo.

## Estrutura de dados

### Tabelas da Fase 1 (existentes)

| Tabela | Propósito |
|--------|-----------|
| `vendors` | Cadastro de vendedores (admin CRUD) |
| `client_wallets` | Vínculo cliente↔vendedor |
| `wallet_movements` | Histórico de atribuições |
| `client_status_history` | Histórico de status |
| `prospecting_flags` | Suspeitas antifraude |
| `prospecting_reviews` | Revisões de suspeitas |
| `client_potentiality` | Potencialidade enriquecida |
| `carteira_raw` | 308k registros importados (25 colunas) |

### Tabelas da Fase 2 (novas)

| Tabela | Propósito |
|--------|-----------|
| `vendor_users` | Vendedores importados da carteira_raw (6.517) |
| `vendor_notes` | Notas estruturadas do vendedor por cliente |
| `segment_services` | Serviços disponíveis por segmento de mercado |
| `client_strategies` | Estratégias compostas pelo vendedor por cliente |
| `client_locations` | Lat/long dos clientes para proximidade |
| `system_messages` | Mensagens editáveis pelo admin |

### Esquema: vendor_users

```
vendor_users
├── id (PK)
├── matricula (VARCHAR 20, UNIQUE) — de carteira_raw.matricula_mcmcu
├── nome (VARCHAR 200)
├── email (VARCHAR 255, nullable)
├── perfil_vendedor (VARCHAR 50) — ACOM, GC, CEM, AGF, CAC, etc.
├── se (VARCHAR 5) — superintendência
├── gerencia (VARCHAR 50)
├── mtr_coordenador (VARCHAR 20, nullable)
├── nome_coordenador (VARCHAR 120, nullable)
├── gerencia_vendas (VARCHAR 200, nullable)
├── shield_user_id (INT, nullable) — preenchido no 1º login
├── ativo (BOOLEAN, default true)
├── created_at, updated_at
```

### Esquema: vendor_notes

```
vendor_notes
├── id (PK)
├── matricula_vendedor (VARCHAR 20, NOT NULL)
├── cnpj (VARCHAR 14, NOT NULL)
├── tipo (VARCHAR 30) — visita, observacao, contato, reuniao, estrategia
├── conteudo (TEXT)
├── sentimento (VARCHAR 15, nullable) — positivo, neutro, negativo
├── created_at
```

### Esquema: segment_services

```
segment_services
├── id (PK)
├── segmento_mercado (VARCHAR 150)
├── servico_nome (VARCHAR 100) — SEDEX, PAC, Logística Reversa
├── servico_descricao (TEXT)
├── icone (VARCHAR 50)
├── cor (VARCHAR 7) — hex
├── ordem (INT)
├── ativo (BOOLEAN)
```

### Esquema: client_strategies

```
client_strategies
├── id (PK)
├── matricula_vendedor (VARCHAR 20)
├── cnpj (VARCHAR 14)
├── service_id (INT, FK → segment_services)
├── observacao (TEXT, nullable)
├── created_at
```

### Esquema: client_locations

```
client_locations
├── id (PK)
├── cnpj (VARCHAR 14, UNIQUE)
├── latitude (DECIMAL 10,7)
├── longitude (DECIMAL 10,7)
├── endereco_formatado (VARCHAR 255, nullable)
├── registrado_por (VARCHAR 20) — matrícula
├── created_at, updated_at
```

### Esquema: system_messages

```
system_messages
├── id (PK)
├── slug (VARCHAR 50, UNIQUE)
├── titulo (VARCHAR 200)
├── conteudo (TEXT) — HTML rico
├── ativo (BOOLEAN)
├── updated_by (INT, nullable)
├── created_at, updated_at
```

## Rotas da Fase 2

```
# Vendedor
GET  /vendedor              → dashboard
GET  /vendedor/clientes     → cards (JSON API para swipe)
GET  /vendedor/cliente/:cnpj → detalhe (3 camadas)
POST /vendedor/nota          → registrar nota/visita
POST /vendedor/estrategia    → salvar blocos
POST /vendedor/localizacao   → cadastrar lat/long
GET  /vendedor/perfil        → dados do vendedor

# Coordenador
GET  /coordenador            → dashboard do time
GET  /coordenador/vendedores → lista do time
GET  /coordenador/vendedor/:m → carteira de um vendedor

# Sem carteira
GET  /sem-carteira           → tela informativa

# Admin (novas rotas)
GET  /admin/mensagens        → lista de mensagens
GET  /admin/mensagens/:slug  → editar
POST /admin/mensagens/:slug  → salvar
```

## Estratégia de UI

- Mobile-first: design pensado primeiro para celular, adaptado para desktop.
- Cards grandes com toque/swipe para navegação intuitiva.
- Cores e ícones contextuais por categoria, segmento e ciclo de vida.
- 3 camadas de dados em abas ou accordion para não sobrecarregar.
- Drag & drop de blocos de serviço para composição de estratégias.
- Rich text editor (TinyMCE CDN) para mensagens do sistema.

## Riscos e cuidados

- A integração com LDAP pode exigir ajuste de mapeamento de atributos.
- O swipe em cards precisa funcionar bem em iOS e Android (touch events).
- O drag & drop de blocos deve ter fallback para toque em mobile.
- A tabela `vendors` e `vendor_users` coexistem temporariamente — evitar inconsistências.
- O volume de dados por vendedor (média ~47 clientes) é gerenciável para cards.

## Próximo passo

Executar as tasks da Fase 2 conforme definido em `tasks.md`.