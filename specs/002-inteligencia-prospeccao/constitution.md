# Constitution 002 - Inteligência de Prospecção e Enriquecimento SPIV

## Objetivo

Esta constituição define as diretrizes e os princípios de produto e engenharia de dados que devem reger a Fase 3 do SPIV (Inteligência de Prospecção e Enriquecimento Preditivo).

## Princípios não negociáveis

1. **Privacidade e Governança de Dados (LGPD por Design):**
   - O SPIV lida estritamente com dados de Pessoas Jurídicas (CNPJ). Não devem ser persistidos dados pessoais de pessoas físicas (CPFs, telefones pessoais ou e-mails de titulares) oriundos de buscas automáticas OSINT.
   - Todo scraping ou rastreamento de dados deve obedecer às diretivas de robôs (`robots.txt`) dos portais inspecionados.

2. **Desoneração Financeira Corporativa (API Keys Descentralizadas):**
   - Para integrações externas pagas ou com limites restritos (como APIs de SERP ou enriquecimento de CNPJ), o sistema deve suportar credenciais descentralizadas cadastradas pelo próprio vendedor em sua tela de configurações, usando a cota global do `.env` apenas como fallback/reserva.

3. **Arquitetura de Banco Flexível (JSONB):**
   - O armazenamento de dados dinâmicos inspecionados (como tecnologias web descobertas ou logs de sinalizações de vagas) deve ser feito no campo `JSONB` no PostgreSQL, garantindo escalabilidade sem a necessidade de constantes migrations estruturais de banco.

4. **Resiliência a Falhas de Integração (Graceful Degradation):**
   - A falha de qualquer chamada de API externa de inteligência comercial (timeout, cota estourada, erro SSL) jamais deve causar interrupção ou lentidão na renderização do app principal do vendedor.

5. **Transparência e Explicabilidade do Score:**
   - Todo lead qualificado ou score preditivo gerado pelo sistema deve ter justificativas explícitas exibidas ao vendedor (ex: *"Score 10: Empresa atua no varejo e utiliza o gateway Frenet"*). A IA e os algoritmos do SPIV nunca devem funcionar como caixas-pretas inexplicáveis.

6. **Auditoria de Ações de Inteligência:**
   - Toda validação ou recusa de leads baseada em dados sugeridos pelo sistema deve ser gravada na tabela de auditoria com matrícula do vendedor (`validated_by`) e carimbo de tempo.

7. **Recalque Assíncrono do Score Preditivo (Performance do Banco):**
   - O recálculo de scores preditivos em massa (após alteração de parâmetros pelo admin) nunca deve ser rodado de forma síncrona na thread HTTP do servidor Apache/Nginx para evitar timeouts e travamentos da base de dados PostgreSQL.
   - A atualização deve ser feita de forma assíncrona usando processos em segundo plano (background tasks) com polling de progresso AJAX no frontend.
