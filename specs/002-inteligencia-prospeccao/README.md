# Inteligência de Negociações — Plano de Vendas 2026

> 🔒 **Dado sensível — uso interno. Não expor em nenhuma funcionalidade do sistema por enquanto.**

## O que é

Este spec documenta a ingestão e análise do arquivo `adm/negociacoes.csv`,
que contém **22.433 negociações** registradas no SAD pelos vendedores/ACOMs
da Gerência Regional SPM, referentes ao Plano de Vendas 2026 dos Correios.

## Fonte de dados

| Arquivo | Descrição | Linhas |
|---|---|---|
| `adm/negociacoes.csv` | Exportação do SAD — negociações PV 2026 | 22.433 |
| `adm/plano_de_vendas_2026_vf_09_01_2026.pdf` | Documento oficial do PV 2026 (DEVEN/SUCAN/DINEG) | 49 pgs |

## Tabelas criadas

### `plano_de_vendas`
20 ações do PV 2026, com hashtag, nome, detalhe e objetivo extraídos do Apêndice I do PDF.

### `negociacoes`
22.433 registros com: cliente, hashtag, status, resultado, receita prevista/realizada,
força de vendas, segmento, tipo, data de cadastro.

## Scripts

| Script | Função |
|---|---|
| `scratch/importar_negociacoes.py` | Import do CSV → PostgreSQL (idempotente) |
| `scratch/profile_csv.py` | Análise exploratória do CSV |
| `scratch/read_pdf.py` | Extração de texto do PDF |
| `scratch/report_data.json` | Dados agregados para o relatório |

## Relatório

📊 **[relatorio_negociacoes_pv2026.html](relatorio_negociacoes_pv2026.html)**
Relatório HTML completo, pronto para salvar como PDF (Ctrl+P → Salvar como PDF).

## Principais números

| Indicador | Valor |
|---|---|
| Total de negociações | 22.433 |
| Receita prevista total | R$ 1,301 bi |
| Receita realizada | R$ 305,1 mi |
| Taxa de conversão geral | 23,4% |
| Negociações concluídas | 6.269 (27,9%) |
| Negociações em andamento | 4.541 (R$ 1,24 bi em pipeline) |
| Ameaças mapeadas | 336 (-R$ 523 mi em risco) |
| Ação mais usada | AUMENTODESHARE (10.290 neg, 45,9%) |
| Ação mais eficiente | LOGREVERSA (74% de conversão) |

## Status das tarefas

- [x] Leitura e análise do CSV (`adm/negociacoes.csv`)
- [x] Leitura do PDF oficial do PV 2026 (49 páginas)
- [x] Migration `2026-07-22-100006_CreateNegociacoesTables.php` criada e executada
- [x] Import dos 22.433 registros no banco (`python scratch/importar_negociacoes.py`)
- [x] Tabela `plano_de_vendas` populada com 20 ações e descrições do Apêndice I
- [x] Relatório HTML de inteligência gerado (`relatorio_negociacoes_pv2026.html`)
- [ ] Funcionalidade no sistema (bloqueado — dado sensível, aguardando autorização)

## Decisões de design

- **Sem FK hard**: a tabela `negociacoes` não tem FK obrigatória para `plano_de_vendas.hashtag`
  para não rejeitar registros com hashtags incomuns ou nulas durante o import.
- **ON CONFLICT DO NOTHING**: o import é idempotente — pode ser reexecutado sem duplicar dados.
- **`detalhe_da_acao` e `objetivo_da_acao`**: preenchidos automaticamente a partir do Apêndice I do PDF.
  O usuário pode editar esses campos conforme o plano evolui.

## Próximos passos (quando autorizado)

1. Dashboard de acompanhamento das ações por hashtag
2. Cruzamento com a carteira de clientes (`client_wallets`)
3. Alertas de negociações em risco (Ameaças sem Contramedida)
4. Ranking de ACOMs por taxa de conversão
