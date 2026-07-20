# Plan 002 - Planejamento Técnico da Fase 3

## Arquitetura da Solução

O módulo de inteligência de vendas será desenvolvido em PHP (CodeIgniter 4) integrado ao PostgreSQL. Ele consumirá dados da base local da Receita Federal (tabela `receita.estabelecimentos` e `receita.empresas`), efetuará chamadas cURL isoladas e persistirá os enriquecimentos no banco de dados para evitar consultas de rede redundantes.

---

## 1. Modelagem do Banco de Dados (PostgreSQL)

Criaremos uma nova tabela de enriquecimento para armazenar as descobertas OSINT e cálculos de pontuação:

```sql
CREATE TABLE client_enrichment (
    cnpj VARCHAR(14) PRIMARY KEY,
    website_domain VARCHAR(255) NULL,
    technologies JSONB NULL,          -- Array de strings: ["shopify", "frenet"]
    job_signals JSONB NULL,           -- Detalhes das vagas encontradas
    logistics_score INT DEFAULT 1,    -- Score unificado de propensão logística (1 a 10)
    score_justification VARCHAR(255),  -- Texto explicativo do score
    enriched_at TIMESTAMP NULL,       -- Data/Hora do último enriquecimento OSINT
    created_at TIMESTAMP NOT NULL,
    updated_at TIMESTAMP NOT NULL,
    CONSTRAINT fk_enrichment_client FOREIGN KEY (cnpj) REFERENCES carteira_raw(cnpj) ON DELETE CASCADE
);

-- Indexação para buscas rápidas e ordenação
CREATE INDEX idx_enrichment_score ON client_enrichment(logistics_score DESC);
```

### Configurações de Pesos e Parâmetros de Scoring
Adicionaremos as tabelas para suportar a parametrização em tempo real pelo administrador:

```sql
-- Guarda as chaves e valores globais do Score de 0 a 100
CREATE TABLE scoring_config (
    key VARCHAR(50) PRIMARY KEY,
    value VARCHAR(255) NOT NULL,
    updated_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP NOT NULL
);

-- Regras de pesos individuais de cada código de CNAE
CREATE TABLE cnae_scoring_rules (
    cnae_code VARCHAR(7) PRIMARY KEY,
    weight INT CHECK (weight >= 0 AND weight <= 100),
    description VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL,
    updated_at TIMESTAMP NOT NULL
);

-- Adiciona a coluna de API Key pessoal do vendedor
ALTER TABLE vendor_users ADD COLUMN serper_api_key VARCHAR(255) NULL;
```

---

## 2. Query de Recálculo em Massa com Amortização (PostgreSQL)

A query de recalque deve ler os CNAEs Principal e Secundários de `receita.estabelecimentos` e associar aos pesos cadastrados. 
A coluna `cnae_fiscal_secundaria` é uma string separada por vírgulas. Usamos `string_to_array` e `unnest` do PostgreSQL para ler cada código e aplicar o **Fator de Amortização** de forma unificada:

```sql
WITH cnae_scores AS (
    SELECT 
        e.cnpj_basico || e.cnpj_ordem || e.cnpj_dv AS cnpj,
        
        -- 1. Peso do CNAE Principal (100% do peso da regra)
        COALESCE(p_rule.weight, 0) AS principal_score,
        
        -- 2. Maior peso entre todos os CNAEs Secundários (multiplicado pelo Fator de Amortização, ex: 0.70)
        COALESCE((
            SELECT MAX(s_rule.weight * :amortization_factor::float)
            FROM unnest(string_to_array(e.cnae_fiscal_secundaria, ',')) AS sec_cnae
            LEFT JOIN cnae_scoring_rules s_rule ON s_rule.cnae_code = sec_cnae
        ), 0) AS secundario_score
        
    FROM receita.estabelecimentos e
    LEFT JOIN cnae_scoring_rules p_rule ON p_rule.cnae_code = e.cnae_fiscal_principal
)
UPDATE client_enrichment ce
SET 
    -- O score final do bloco de CNAE será o maior valor entre o Principal e o Secundário amortizado
    logistics_score = GREATEST(cs.principal_score, cs.secundario_score),
    updated_at = NOW()
FROM cnae_scores cs
WHERE ce.cnpj = cs.cnpj;
```

---

## 3. Fluxo de Execução Assíncrono do Recálculo

Para evitar timeouts na requisição do Admin:
1.  **POST `/admin/scoring/recalcular`:** O administrador clica no botão. O controller cria um arquivo de lock e inicia a tarefa CLI `php spark enrich:recalculate` em segundo plano (usando `shell_exec` ou background job runner). Retorna `success => true`.
2.  **Job CLI (`Enrichment::recalculate`):** A tarefa lê o total de registros. Divide o processamento em chunks de 5.000 CNPJs e atualiza uma chave em memória (Redis/Cache do CodeIgniter) contendo a porcentagem concluída: `scoring_recalculation_progress = 45`.
3.  **GET `/admin/scoring/progresso`:** A tela do admin faz chamadas de polling AJAX a cada 2 segundos a esta rota para ler a chave de cache e mover a barra de progresso no frontend.

---

## 4. Serviço de Detecção de E-commerce (`ECommerceDetector`)

Criaremos uma classe de serviço em `app/Services/ECommerceDetector.php` encarregada de raspar e identificar tecnologias:

```php
namespace App\Services;

class ECommerceDetector 
{
    public function detect(string $domain): array 
    {
        $url = $this->normalizeUrl($domain);
        $client = \Config\Services::curlrequest();
        
        try {
            $response = $client->get($url, [
                'timeout' => 5, // Timeout baixo para evitar travamento da thread
                'headers' => [
                    'User-Agent' => 'SPIV-OSINT-Bot/1.0 (+https://spiv.dev)'
                ],
                'http_errors' => false
            ]);
            
            if ($response->getStatusCode() !== 200) {
                return [];
            }
            
            $html = $response->getBody();
            $detected = [];
            
            // Regras de detecção
            if (stripos($html, 'cdn.shopify.com') !== false || stripos($html, 'Shopify.theme') !== false) {
                $detected[] = 'shopify';
            }
            if (stripos($html, 'wp-content/plugins/woocommerce') !== false) {
                $detected[] = 'woocommerce';
            }
            if (stripos($html, 'nuvemshop') !== false || stripos($html, 'tiendanube') !== false) {
                $detected[] = 'nuvemshop';
            }
            if (stripos($html, 'tray.com.br') !== false) {
                $detected[] = 'tray';
            }
            if (stripos($html, 'vtex.js') !== false || stripos($html, 'vteximg.com.br') !== false) {
                $detected[] = 'vtex';
            }
            
            return $detected;
            
        } catch (\Exception $e) {
            return [];
        }
    }
}
```
