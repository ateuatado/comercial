# Spec — Fase 3: Gestão Avançada do Coordenador

> **Status:** ✅ Fechada — todas as decisões tomadas. Tasks adicionadas ao `tasks.md`. Pronta para implementação.

---

## 1. Contexto e motivação

A página atual do **Coordenador** (`/coordenador`) oferece apenas **visão de leitura** do time:

- Lista dos vendedores subordinados com KPIs de carteira.
- Visualização read-only da carteira de cada vendedor.
- Acesso ao detalhe de um vendedor específico.

O modelo atual não permite ao coordenador **agir** — toda ação de gestão depende do Admin.

Esta spec define a **expansão da autonomia operacional do Coordenador**, com escopo restrito à própria gerência e rastreabilidade completa das ações.

---

## 2. Papéis de usuário no SPIV

### 2.1 Tabela de papéis (revisada)

| Papel | Quem é | Acesso base | Escopo |
|---|---|---|---|
| **Admin** | TI / gestão central | `/admin/dashboard` | Global — sem restrição |
| **Coordenador** | Gerente de equipe comercial | `/coordenador` | Restrito à **própria gerência** |
| **Vendedor** | ACOM, GC, CEM, AGF, etc. | `/vendedor` | Apenas a própria carteira |
| **Sem carteira** | Funcionário sem vínculo | `/sem-carteira` | Tela informativa |

### 2.2 O que muda com esta spec

**Coordenador ganha capacidade de (tudo imediato, sem aprovação):**

1. **Cadastrar, editar e desativar vendedores** do próprio time.
2. **Atribuir clientes livres** a vendedores do time.
3. **Transferir clientes** entre vendedores do time.
4. **Transferir vendedores** para outro coordenador da **mesma gerência**.

**Admin mantém capacidade ampla** (não muda):

- Distribuição automática de toda a base.
- Reatribuição global sem restrição de escopo.
- Transferência de vendedores entre gerências distintas.

---

## 3. A hierarquia organizacional e o escopo do coordenador

### 3.1 Estrutura real dos Correios (simplificada para o MVP)

```
SE (Superintendência Estadual)
 └── Gerência (ex: GR Metropolitana A, GR Interior Campinas)
      └── Coordenador  ←  fronteira de operação desta spec
           └── Vendedores
```

> **Decisão de negócio (confirmada):** O coordenador opera **dentro da sua gerência**. Não opera em outras gerências, mesmo que dentro da mesma SE.

### 3.2 Como isso está nos dados

A `vendor_users` já possui os campos necessários:

| Campo | Propósito | Uso como fronteira |
|---|---|---|
| `se` | Superintendência (ex: `SPM`, `SP`) | Filtro secundário |
| `gerencia` | Gerência regional (ex: `GR Metropolitana A`) | **Fronteira principal de operação** |
| `mtr_coordenador` | Matrícula do coordenador responsável | Identifica o time |
| `nome_coordenador` | Nome do coordenador | Informativo |
| `gerencia_vendas` | Força de vendas (ex: `GEVEN SP`) | Contexto adicional |

### 3.3 Regra de fronteira

> **Um coordenador só pode operar sobre registros onde `gerencia = gerencia do coordenador logado`.**

Isso protege contra:
- Coordenador A atribuir clientes que "pertencem" à gerência B.
- Coordenador A transferir vendedor para coordenador de outra gerência (isso é papel do Admin).

### 3.4 Problema de dados: gerência pode ser inconsistente

Os campos `gerencia` da `vendor_users` vêm da `carteira_raw` importada e podem ter valores heterogêneos. **Antes da implementação das guards de escopo, é necessário validar a consistência desses valores na base.**

> [!WARNING]
> A fronteira de gerência só funciona bem se o campo `gerencia` estiver consistentemente preenchido na `vendor_users`. Uma task específica de diagnóstico deve ser executada antes das guards.

---

## 4. Funcionalidades detalhadas

### 4.1 — CRUD completo de vendedores pelo coordenador

O coordenador tem controle total sobre os vendedores **do próprio time**.

**Cadastrar novo vendedor:**

| Campo | Obrigatório | Regra |
|---|---|---|
| `matricula` | ✅ | Deve ser única na base. Qualquer funcionário ativo pode ser cadastrado. |
| `nome` | ✅ | Livre |
| `email` | ❌ | Livre |
| `perfil_vendedor` | ✅ | Lista: ACOM, GC, CEM, AGF, CAC |
| `se` | ✅ | Herdado automaticamente do coordenador (bloqueado) |
| `gerencia` | ✅ | Herdado automaticamente do coordenador (bloqueado) |
| `mtr_coordenador` | Auto | Fixado na matrícula do coordenador logado (bloqueado) |
| `nome_coordenador` | Auto | Fixado no nome do coordenador logado (bloqueado) |
| `ativo` | Auto | `true` por padrão |

**Editar vendedor do time:** todos os campos acima, exceto `se`, `gerencia`, `mtr_coordenador`, `nome_coordenador` (que seguem as regras do time).

**Desativar vendedor:** o coordenador pode desativar (`ativo = false`) um vendedor do time. Os clientes do vendedor desativado **não são redistribuídos automaticamente** — ficam como "órfãos" e podem ser redistribuídos manualmente.

> [!NOTE]
> Qualquer funcionário ativo dos Correios pode ser cadastrado como vendedor (sem pré-requisito de matrícula existente na base importada). O sistema criará o registro de `vendor_users` do zero.

---

### 4.2 — Atribuição de clientes livres a um vendedor

O coordenador pode atribuir CNPJs sem dono atual para vendedores do seu time.

**Fluxo:**
1. Coordenador acessa "Clientes Livres" — CNPJs sem `matricula_mcmcu` na `carteira_raw` E sem vínculo ativo em `client_wallets`, filtrados pela **gerência do coordenador** (via `gerencia` da `carteira_raw`).
2. Seleciona um ou mais clientes (busca por CNPJ/razão social, checkbox + "Selecionar tudo").
3. Escolhe o vendedor destino (dropdown do time — somente ativos).
4. Confirma com modal de confirmação.
5. Sistema insere em `client_wallets` com `origem_atribuicao = 'coordenador'` e registra em `wallet_movements`.

**Regras:**
- Escopo: `gerencia` da `carteira_raw` deve bater com `gerencia` do coordenador.
- Vendedor destino deve estar ativo e pertencer ao time.
- Auditoria completa: `atribuido_por` (matrícula do coordenador), `atribuido_em`, `origem_atribuicao = 'coordenador'`.

---

### 4.3 — Transferência de clientes entre vendedores do time

**Fluxo:**
1. Coordenador acessa a carteira do vendedor A (view existente, agora com checkboxes).
2. Seleciona clientes via checkbox.
3. Clica em "Transferir selecionados".
4. Escolhe vendedor B (dropdown do time, excluindo A).
5. Informa motivo (campo texto, obrigatório).
6. Confirma via modal.
7. Sistema atualiza `client_wallets` e insere em `wallet_movements`.

**Regras:**
- Vendedores A e B devem pertencer ao time do coordenador logado.
- Motivo obrigatório.
- Histórico anterior preservado — apenas o vínculo ativo muda.
- Efeito imediato.

---

### 4.4 — Transferência de vendedor para outro coordenador

**Fluxo:**
1. Coordenador acessa o detalhe de um vendedor do time.
2. Clica em "Transferir vendedor".
3. Vê lista de **outros coordenadores ativos da mesma gerência** (não da mesma SE — da mesma gerência).
4. Escolhe o coordenador destino.
5. Informa motivo (obrigatório).
6. Confirma via modal.
7. Sistema atualiza `mtr_coordenador` e `nome_coordenador` em `vendor_users`. Os **clientes do vendedor não mudam de dono** — apenas o vínculo hierárquico muda.

**Regras:**
- Só pode transferir vendedores do próprio time.
- Coordenador destino deve estar ativo e na mesma gerência.
- Efeito imediato, sem aprovação.
- Ação auditada (ver Q3 abaixo).

---

## 5. Decisões técnicas (todas fechadas)

### ✅ Q3 — Auditoria de transferência de vendedores — **Opção B escolhida**

Criada nova tabela `vendor_movements` separada de `wallet_movements`. Justificativa: vendedores e clientes são entidades distintas; misturar os dois na mesma tabela de movimentos geraria ambiguidade nas auditorias.

```sql
vendor_movements
├── id             (PK)
├── matricula      (VARCHAR 20) -- vendedor movimentado
├── coord_origem   (VARCHAR 20) -- matrícula coord. de origem
├── coord_destino  (VARCHAR 20) -- matrícula coord. de destino
├── gerencia       (VARCHAR 100) -- gerência da operação
├── motivo         (TEXT)
├── feito_por      (VARCHAR 20) -- matrícula do executor
├── created_at
```

---

## 6. Impacto em dados e arquitetura

### 6.1 Tabelas impactadas

| Tabela | Tipo de impacto |
|---|---|
| `vendor_users` | INSERT (novo vendedor), UPDATE (edição, desativação, transferência de coordenador) |
| `client_wallets` | INSERT/UPDATE (atribuição e transferência de clientes) |
| `wallet_movements` | INSERT (auditoria de movimentações de clientes) |
| `carteira_raw` | SELECT (consulta de clientes livres por gerência) |

### 6.2 Campo novo sugerido em `wallet_movements`

Adicionar `realizado_por_perfil VARCHAR(20)` com valores `'admin'`, `'coordenador'`, `'sistema'` para facilitar filtros de auditoria.

### 6.3 Nova tabela `vendor_movements` (se Q3 → Opção B)

Ver esquema na Q3 acima.

### 6.4 Guard de gerência no `CoordenadorController`

Toda operação precisa verificar que os recursos (vendedores, clientes) pertencem à gerência do coordenador logado. Isso requer um helper/método reutilizável:

```php
// Exemplo de guard de escopo
private function assertMesmaGerencia(string $matriculaAlvo): void
{
    $alvo = $this->vendorModel->findByMatricula($matriculaAlvo);
    if (!$alvo || $alvo['gerencia'] !== $this->getLoggedVendor()['gerencia']) {
        throw new \RuntimeException('Acesso negado: fora do escopo da gerência.');
    }
}
```

---

## 7. Novas rotas

```
# Coordenador — Gestão de vendedores
GET  /coordenador/vendedores/novo            → formulário de cadastro
POST /coordenador/vendedores/salvar          → salva novo vendedor
GET  /coordenador/vendedor/:m/editar         → formulário de edição
POST /coordenador/vendedor/:m/atualizar      → salva edição
POST /coordenador/vendedor/:m/desativar      → desativa vendedor

# Coordenador — Transferência de vendedor entre coordenadores
GET  /coordenador/vendedor/:m/transferir     → formulário de transferência
POST /coordenador/vendedor/:m/transferir     → processa transferência

# Coordenador — Clientes livres + atribuição
GET  /coordenador/clientes-livres            → lista de clientes sem dono na gerência
POST /coordenador/clientes-livres/atribuir   → atribui CNPJs selecionados a vendedor

# Coordenador — Transferência de clientes entre vendedores
POST /coordenador/vendedor/:m/transferir-clientes → move clientes selecionados
```

---

## 8. UX e design

- Manter padrão visual atual (Bootstrap 5 + design system do projeto).
- **Confirmação modal** obrigatória antes de qualquer ação destrutiva (transferência, desativação).
- Flash messages de sucesso/erro em todas as ações.
- Listas de clientes e vendedores com busca/filtro — volume pode ser grande.
- Checkboxes com "Selecionar tudo" para operações em lote.
- Campo de motivo sempre visível em transferências (não pode ser ignorado).
- Indicador visual de escopo: o coordenador deve sempre ver claramente que está operando dentro da sua gerência.

---

## 9. Tasks da Fase 3 (prontas para incluir no `tasks.md` após Q3 decidida)

### 9.1 Preparação de dados

- [ ] **3.0.1** — Diagnóstico: verificar consistência do campo `gerencia` em `vendor_users` (quantos registros têm gerência preenchida, quantos estão nulos).
- [ ] **3.0.2** — Migration: adicionar coluna `realizado_por_perfil` em `wallet_movements`.
- [ ] **3.0.3** — Migration: criar tabela `vendor_movements` (se Q3 → Opção B).

### 9.2 CRUD de vendedores pelo coordenador

- [ ] **3.1.1** — `CoordenadorController::novoVendedor()` + view formulário.
- [ ] **3.1.2** — `CoordenadorController::salvarVendedor()` com validação: matrícula única, campos obrigatórios, `se`/`gerencia`/`mtr_coordenador` fixados no coordenador logado.
- [ ] **3.1.3** — `CoordenadorController::editarVendedor()` + view formulário de edição.
- [ ] **3.1.4** — `CoordenadorController::atualizarVendedor()` com guard de gerência.
- [ ] **3.1.5** — `CoordenadorController::desativarVendedor()` com confirmação e guard de gerência.
- [ ] **3.1.6** — Adaptar view `coordenador/index.php`: botão "Novo Vendedor" + ações (Editar, Desativar) por vendedor.
- [ ] **3.1.7** — Adaptar view `coordenador/vendedor_detalhe.php`: botões de ação (Editar, Desativar, Transferir).

### 9.3 Clientes livres e atribuição

- [ ] **3.2.1** — `CoordenadorController::clientesLivres()` com filtro por `gerencia` do coordenador.
- [ ] **3.2.2** — View `coordenador/clientes_livres.php` com busca, checkboxes, dropdown de vendedor destino.
- [ ] **3.2.3** — `CoordenadorController::atribuirClientes()` com validação de escopo e auditoria.

### 9.4 Transferência de clientes entre vendedores

- [ ] **3.3.1** — Adaptar view `coordenador/vendedor_clientes.php`: adicionar checkboxes + botão "Transferir selecionados" + modal de confirmação com dropdown e campo motivo.
- [ ] **3.3.2** — `CoordenadorController::processarTransferenciaClientes()` com validação de escopo (A e B no time), motivo obrigatório, atualização de `client_wallets` e `wallet_movements`.

### 9.5 Transferência de vendedor entre coordenadores

- [ ] **3.4.1** — `CoordenadorController::formTransferirVendedor()` + view com lista de coordenadores da mesma gerência.
- [ ] **3.4.2** — `CoordenadorController::processarTransferenciaVendedor()` com guard de gerência, atualização de `vendor_users`, registro em `vendor_movements` (ou `wallet_movements`).

### 9.6 Testes de isolamento

- [ ] **3.5.1** — Teste: coordenador não vê clientes livres de outra gerência.
- [ ] **3.5.2** — Teste: coordenador não consegue transferir vendedor para coordenador de outra gerência.
- [ ] **3.5.3** — Teste: coordenador não consegue editar/desativar vendedor que não é do seu time.
- [ ] **3.5.4** — Teste: auditoria correta em `wallet_movements`/`vendor_movements` após cada ação.

---

## 10. Critérios de aceite (pré-implementação)

- [x] Q1 — Fluxo de aprovação de transferência → **Imediata, sem aprovação.**
- [x] Q2 — Escopo de coordenadores destino → **Mesma gerência.**
- [x] Q3 — Estratégia de auditoria → **Nova tabela `vendor_movements` (Opção B).**
- [x] Q4 — Clientes livres → **Qualquer funcionário ativo pode ser cadastrado; clientes filtrados por gerência.**
- [x] Q5 — Edição de vendedores → **Completo: criar, editar e desativar.**
- [x] Tasks adicionadas ao `tasks.md` (Fase 3, tasks 3.0 a 3.6).
- [ ] Diagnóstico de `gerencia` na base real (task 3.0.1 — executar após importação real).
