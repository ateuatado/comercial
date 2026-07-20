# Tasks 002 - Tarefas da Fase 3

Este documento lista todas as tarefas necessárias para a implementação da Fase 3 de Inteligência de Prospecção do SPIV.

## Fase 3 - Inteligência de Prospecção e Enriquecimento Preditivo

### 1. Banco de Dados e Modelagem (DB)
- [ ] Criar arquivo de migração para a tabela `client_enrichment`.
- [ ] Criar arquivo de migração para adicionar `serper_api_key` na tabela `vendor_users`.
- [ ] Rodar as migrations e validar a criação das tabelas no PostgreSQL.
- [ ] Criar índices para a coluna `logistics_score` na tabela `client_enrichment`.

### 2. Implementações do Backend (Services)
- [ ] Desenvolver classe `App\Services\ECommerceDetector.php` com suporte a timeouts e regras regexp.
- [ ] Criar arquivo de configuração `App\Config\LogisticsPropensity.php` com o mapeamento inicial de CNAEs.
- [ ] Implementar classe de utilidade de cálculo de Score preditivo baseado em CNAEs e E-commerce ativo.
- [ ] Desenvolver comando Spark CLI `php spark enrich:leads` para rodar o enriquecimento assíncrono em lote na carteira de clientes.

### 3. Integrações Externas (OSINT)
- [ ] Ajustar o método de cURL da Serper.dev para extrair dados estruturados de vagas (Job Recruiting).
- [ ] Desenvolver regex de correspondência e tokenização para validar se as vagas encontradas pertencem ao segmento operacional logístico.
- [ ] Implementar fallback transparente caso as chaves Serper do vendedor estejam esgotadas.

### 4. Interface do Usuário (Views & JS)
- [ ] Adicionar campo "Chave de API do Serper Pessoal" na página de perfil/configurações do Vendedor.
- [ ] Ajustar a view de listagem de clientes para exibir as colunas de Score de Propensão e Badges.
- [ ] Implementar os filtros rápidos ("Somente E-commerce", "Somente com Vagas", "Score > 7") via AJAX na listagem de clientes.
- [ ] Adicionar seção visual de Inteligência Comercial na tela de detalhes do cliente (`cliente_detalhe.php`), exibindo:
  - Barra de score de 1 a 10 de relevância logística.
  - Justificativa do Score em formato legível.
  - Badges com ícones correspondentes às plataformas e vagas detectadas.

### 5. Área Administrativa (Lookalike)
- [ ] Criar módulo de visualização gerencial para o coordenador/administrador visualizar a média de faturamento por CNAE de contratos ativos.
- [ ] Desenvolver consulta Lookalike no banco para sugerir CNPJs livres com perfil semelhante ao dos campeões atuais.
- [ ] Implementar tela de atribuição em massa de leads sugeridos pelo lookalike para a carteira dos vendedores.
