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

### Configuração de Chaves API Individuais
Na tabela `vendor_users` (ou tabela equivalente de perfis de vendedores), adicionaremos uma coluna para chaves de API:

```sql
ALTER TABLE vendor_users ADD COLUMN serper_api_key VARCHAR(255) NULL;
```

---

## 2. Serviço de Detecção de E-commerce (`ECommerceDetector`)

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
            
            // Regras léxicas de detecção
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
            if (stripos($html, 'frenet') !== false) {
                $detected[] = 'frenet';
            }
            if (stripos($html, 'melhorenvio') !== false) {
                $detected[] = 'melhorenvio';
            }
            
            return $detected;
            
        } catch (\Exception $e) {
            return [];
        }
    }
}
```

---

## 3. Algoritmo de Cálculo de Score por CNAEs

Criaremos um arquivo de configuração estático `app/Config/LogisticsPropensity.php` contendo a tabela de pontuação dos códigos CNAE:

```php
namespace Config;

class LogisticsPropensity extends \CodeIgniter\Config\BaseConfig
{
    public array $scores = [
        // CNAEs de Comércio Varejista (Score 10)
        '4781400' => 10, // Comércio varejista de artigos do vestuário
        '4782201' => 10, // Comércio varejista de calçados
        '4772500' => 10, // Cosméticos e perfumaria
        '4751201' => 10, // Computadores e periféricos
        '4771701' => 10, // Comércio varejista de produtos farmacêuticos
        
        // CNAEs de Comércio Atacadista e Distribuição (Score 7)
        '4649408' => 7,  // Comércio atacadista de produtos de higiene
        '4686902' => 7,  // Comércio atacadista de embalagens
        
        // CNAEs Industriais leves (Score 5)
        '1412601' => 5,  // Confecção de vestuário
        
        // CNAEs de Serviços locais (Score 1)
        '6911701' => 1,  // Advocacia
        '6202300' => 1,  // Consultoria em TI
    ];
}
```

### Lógica de Pontuação do Cliente
1. Consultar CNAE principal e secundários do cliente no banco.
2. Atribuir o score mapeado a cada CNAE. Se o CNAE não constar na lista de regras, o score padrão será 3 para comércio geral e 1 para serviços gerais.
3. O score final do cliente será o **maior valor individual** encontrado.
4. Se o cliente tiver detecção positiva de e-commerce (ex: usa Shopify), o Score é forçado para **10** de forma prioritária.

---

## 4. Integração OSINT para Job Recruiting (Serper API)

Para encontrar vagas logísticas:
- **Consulta:**
  - `q`: `"{NOME_NORMALIZADO}" (vaga OR contratar) (logistica OR expedicao OR estoque OR e-commerce)`
- **Filtro:**
  - A API do Serper retorna resultados com título e descrição (snippet).
  - O sistema filtra se no título ou snippet aparecem palavras qualificadoras.
  - Caso apareçam, os detalhes da vaga (Título e Link do portal) são estruturados em um array JSON e gravados em `client_enrichment.job_signals`.

---

## 5. UI/UX no Aplicativo do Vendedor

### Painel de Prospecção
A listagem de clientes do vendedor será acrescida de ordenação e filtros rápidos:
*   Filtros: `Somente E-commerce`, `Somente com Vagas Abertas`, `Score > 7`.
*   Ordenação Padrão: `Score de Propensão (Decrescente)`.

### Card do Cliente (Detalhe)
Inserção dos Badges dinâmicos na interface:
*   `🛍️ E-commerce Shopify`
*   `📦 Vaga: Auxiliar de Expedição (LinkedIn)`
*   `⭐ Relevância Comercial: 10 / 10`
*   *Justificativa do Score:* *"Score 10 devido ao CNAE de Comércio Varejista e Plataforma Shopify ativa."*
