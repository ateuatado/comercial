# SPIV — Documentação de APIs Públicas e Integrações OSINT

Este documento registra as APIs externas de dados integradas ao ecossistema do SPIV, seus propósitos, formatos de chamadas, comportamentos e fluxos de persistência no banco de dados.

---

## 1. BrasilAPI (Situação Cadastral de CNPJ)
Utilizada para verificar em tempo real se a empresa (CNPJ completo) está ativa ou baixada/inapta no cadastro geral da Receita Federal.

*   **Provedor:** BrasilAPI (Grátis, público, sem chaves API necessárias)
*   **Endpoint Original:** `GET https://brasilapi.com.br/api/cnpj/v1/{cnpj}`
*   **Endpoint Interno (Proxy):** `GET /vendedor/cnpj/verificar/{cnpj}` (Controlado por `VendedorController::verificarCnpj()`)
*   **Persistência local:** Ao obter sucesso (HTTP 200), o sistema grava o status no banco de dados para evitar re-consultas na API:
    *   Tabela: `client_wallets`
    *   Campos: `rfb_situacao_cadastral` (VARCHAR) e `rfb_verificado_em` (TIMESTAMP)

---

## 2. Nominatim OpenStreetMap (Geocodificação de Endereço)
Utilizada para obter coordenadas geográficas de latitude e longitude do endereço cadastrado da empresa no banco de dados da Receita Federal.

*   **Provedor:** OpenStreetMap Nominatim (Grátis, público, respeitando política de uso e User-Agent)
*   **Endpoint Original:** `GET https://nominatim.openstreetmap.org/search?q={address_query}&format=json&limit=1&countrycodes=br`
*   **Endpoint Interno (Proxy):** `POST /vendedor/cnpj/geolocalizar/{cnpj}` (Controlado por `VendedorController::geolocalizarCnpj()`)
*   **Persistência local:** Grava latitude, longitude e endereço formatado no mapa de localizações do SPIV:
    *   Tabela: `client_locations`
    *   Campos: `latitude` (DECIMAL), `longitude` (DECIMAL), `endereco_formatado` (VARCHAR) e `registrado_por` (Matrícula do Vendedor).

---

## 3. DuckDuckGo HTML Search (OSINT de Redes Sociais)
Mecanismo de busca automatizado utilizado como ferramenta de inteligência de fontes abertas (OSINT) para identificar as contas corporativas de redes sociais do cliente (Instagram, LinkedIn e Facebook) a partir do nome e cidade.

*   **Provedor:** DuckDuckGo HTML Search (Grátis, sem chaves API, limpo de códigos JS complexos)
*   **Endpoint Original:** `GET https://html.duckduckgo.com/html/?q={search_query}`
*   **Endpoint Interno (Proxy):** `GET /vendedor/cnpj/redes-sociais/buscar/{cnpj}` (Controlado por `VendedorController::buscarRedesSociais()`)
*   **Fluxo de Inteligência e Validação:**
    1.  O sistema busca as páginas públicas nos motores usando a expressão: `"{Nome} {Cidade} (site:instagram.com OR site:linkedin.com/company OR site:facebook.com)"`.
    2.  O HTML de resposta é parseado para extrair e decodificar os links originais, filtrando e catalogando por rede social (`instagram`, `linkedin`, `facebook` ou `website`).
    3.  As URLs descobertas são salvas na tabela **`client_social_media`** com o status inicial **`sugestao`**.
    4.  O vendedor acessa o detalhe do cliente e decide:
        *   **Validar:** Dispara `POST /vendedor/cnpj/redes-sociais/validar/{id}`, atualizando o status para `validado`.
        *   **Rejeitar:** Dispara `POST /vendedor/cnpj/redes-sociais/rejeitar/{id}`, marcando a sugestão como `rejeitado` para que não seja mais exibida.
