# Tasks 002 - Checklist de Implementação de Inteligência Comercial (Fase 3)

Esta checklist organiza granularmente o desenvolvimento do módulo de **Scoring Preditivo e Inteligência** da Fase 3 do SPIV. As tarefas estão ordenadas de forma lógica e devem ser marcadas e seguidas rigorosamente.

## 1. Estrutura do Banco de Dados e Migrations

- [ ] Criar migration para a tabela `scoring_config` para gerenciar chaves-valores globais de peso.
- [ ] Criar migration para a tabela `cnae_scoring_rules` para mapear o peso de cada código de CNAE (chave primária de 7 dígitos).
- [ ] Criar migration para a tabela `client_enrichment` com chaves estrangeiras, índices de busca rápida baseados em score e campos JSONB.
- [ ] Criar Seeder inicial (`CnaeRulesSeeder`) populando regras padrão para pelo menos 50 CNAEs de comércio varejista (PAC/SEDEX preferencial) com pesos elevados (ex: 40 pontos).
- [ ] Criar Seeder inicial (`ScoringConfigSeeder`) para gravar os pesos iniciais das categorias:
  - `weight_cnae` = 40
  - `weight_capital` = 20
  - `weight_email` = 15
  - `weight_nome_fantasia` = 10
  - `weight_localizacao` = 15
  - `amortization_factor` = 70 (Fator de Amortização dos CNAEs secundários)

## 2. Interface Administrativa de Parametrizador de Scores (Frontend)

- [ ] Desenvolver a rota `/admin/scoring` associada ao controlador de gerenciamento do admin.
- [ ] Criar a view `app/Views/admin/scoring_config.php` contendo:
  - Formulário com sliders ou campos de número para os 5 blocos do algoritmo.
  - Script JS de validação em tempo real que garante que a soma das 5 categorias resulte obrigatoriamente em **100** antes de habilitar o botão de envio.
  - Controle deslizante (slider) ou input numérico de **Fator de Amortização de CNAEs Secundários** (0% a 100%).
  - Tabela CRUD responsiva conectada à API local para gerenciar a lista de CNAEs específicos mapeados com seus respectivos pesos individuais.
  - Seção de gatilho contendo o botão "Salvar e Recalcular Score da Base".
  - Componente de Barra de Progresso do Bootstrap (invisível por padrão) que aparece e atualiza com animação de preenchimento quando o recalque é disparado.

## 3. Backend e Lógicas de API AJAX (Controllers & CLI Jobs)

- [ ] Desenvolver a rota POST `/admin/scoring/salvar` para persistir as alterações de peso na tabela `scoring_config`.
- [ ] Criar o comando CLI Spark em PHP (`app/Commands/RecalculateScores.php`) que execute a query PostgreSQL CTE com a lógica `unnest()` e `string_to_array()` para processar com performance de alto nível as regras de CNAEs principal e secundário amortizado.
- [ ] O comando Spark deve escrever a porcentagem de conclusão a cada chunk de 5.000 registros no cache de dados da aplicação (`Cache::save('scoring_recalculation_progress', $percent)`).
- [ ] Criar a rota POST `/admin/scoring/recalcular` que aciona de forma segura o comando CLI Spark em background (execução em background que não trava o Apache/Nginx).
- [ ] Criar a rota GET `/admin/scoring/progresso` que lê o percentual salvo em cache e o retorna no formato JSON (`{ "progresso": 72 }`).
- [ ] Implementar a função JavaScript de Polling AJAX na view que faz GETs na rota `/admin/scoring/progresso` a cada 2 segundos até atingir 100%, ocultando a barra e recarregando a página com o aviso de sucesso.

## 4. Testes e Validação de Dados

- [ ] Criar testes unitários simulando CNPJ com CNAE varejista como principal (deve ganhar peso máximo de CNAE).
- [ ] Criar testes unitários simulando CNPJ com CNAE varejista como secundário (deve ganhar peso amortizado, ex: `40 * 0.70 = 28` no bloco).
- [ ] Validar a query PostgreSQL contra cenários em que o campo de e-mail de `receita.estabelecimentos` possui e-mail institucional e público, certificando que o score e a extração funcionem conforme planejado.
