# Spec 002 - Inteligência de Prospecção e Enriquecimento Preditivo

## Objetivo

Detalhar as regras funcionais e de negócio para a Fase 3 do SPIV. Esta fase transforma o sistema em uma ferramenta de enriquecimento e qualificação ativa, permitindo à força de vendas dos Correios prospectar e abordar clientes corporativos com maior assertividade operacional.

---

## 1. Detecção de Tecnologia de E-commerce (Tech Stack Scraping)

### Objetivo de negócio
Identificar se o cliente corporativo é um e-commerce ativo e qual plataforma ele utiliza para as suas vendas online. Isso permite ao vendedor direcionar soluções logísticas avançadas de remessa física (PAC, SEDEX, e-Fulfillment).

### Regras de Negócio
- **Descoberta do Domínio:** O sistema deve buscar o endereço de website associado ao CNPJ cadastrado.
- **Scraping Inofensivo:** O SPIV faz uma requisição HTTP GET leve para o domínio da página inicial. Apenas os cabeçalhos HTTP e o código HTML estático da home page são analisados.
- **Identificação de Plataformas:** O sistema identifica assinaturas no HTML:
  - **Shopify:** Presença do domínio `cdn.shopify.com` ou objeto JS `Shopify`.
  - **WooCommerce:** Presença de links apontando para `wp-content/plugins/woocommerce/`.
  - **Nuvemshop:** Presença de cookies ou metas de controle da Nuvemshop (ex: `nuvemshop` ou `tiendanube`).
  - **Tray:** Presença de caminhos do domínio `tray.com.br` ou variáveis `Tray`.
  - **VTEX:** Presença do script de controle `vtex.js` ou caminhos `vteximg.com.br`.
- **Exibição do Selo:** Se detectada plataforma, o app mobile exibe o badge do e-commerce (ex: `Shopify 🛍️`) em destaque no card de prospecção do cliente.

---

## 2. Painel Administrativo de Configuração de Pesos e Score Preditivo

### Objetivo de negócio
Permitir que a gestão e os coordenadores comerciais parametrizem os critérios de qualificação e gerem um Score Preditivo Unificado (de 0 a 100) para cada CNPJ, priorizando automaticamente os leads mais quentes para a força de vendas do campo.

### O Modelo de Scoring Preditivo (Fatores e Pesos)

O Score Preditivo de cada empresa é a soma das pontuações obtidas em 5 blocos de relevância lógica para os Correios:

| Fator de Pontuação | Relevância Operacional (Porquê) | Peso Padrão | Regra de Negócio |
| :--- | :--- | :--- | :--- |
| **Ramo de Atividade (CNAE)** | Identifica se a empresa atua na comercialização física de bens de pequeno/médio porte transportáveis. | **40 pontos** | CNAEs de Comércio Varejista ganham peso máximo; Serviços locais ganham peso mínimo. |
| **Porte (Capital Social)** | Indica o faturamento potencial da empresa e a probabilidade de grandes remessas frequentes. | **20 pontos** | Empresas com capital social > R$ 100 mil ganham pontos máximos; MEIs pequenos ganham mínimo. |
| **Maturidade Digital (E-mail)** | Indica se a empresa tem e-mail corporativo próprio de seu site, que é a porta de entrada do e-commerce. | **15 pontos** | Domínios próprios (@empresa.com.br) ganham pontos; Provedores públicos (@gmail.com) ganham zero. |
| **Presença Comercial (Marca)** | Filtra empresas burocráticas abertas e sem atividade comercial física de fato. | **10 pontos** | Nome Fantasia preenchido e ativo no banco da Receita Federal soma pontos adicionais. |
| **Localização Estratégica** | Reduz o custo logístico de coleta ao priorizar empresas situadas próximas a centros de distribuição. | **15 pontos** | Leads localizados na mesma cidade de um CDD ou GEVEN dos Correios ganham bônus. |

---

### A Regra de Amortização do CNAE Secundário

Uma empresa pode ter um CNAE Principal de baixo interesse logístico (ex: Fabricação B2B pura) mas possuir CNAEs Secundários de alto interesse (ex: Comércio varejista online).
*   **Regra de Negócio:** Se a correspondência com a regra de peso do CNAE for no **CNAE Principal**, a empresa recebe **100%** dos pontos definidos para aquela regra.
*   **Regra de Amortização:** Se a correspondência for encontrada apenas na lista de **CNAEs Secundários**, a pontuação sofrerá um redutor parametrizável pelo administrador (fator de amortização, ex: **70% do peso original**).
*   **Cálculo Final do Bloco:** O sistema calcula a pontuação do CNAE Principal e de todos os CNAEs Secundários (amortizados), selecionando o **maior valor individual** como a pontuação do bloco de CNAE da empresa.

---

### Funcionalidades do Painel de Gestão (Admin)

1.  **Sliders de Peso por Bloco:** A soma total das 5 categorias (CNAE, Capital Social, E-mail, Nome Fantasia, Localização) deve ser obrigatoriamente **100**. O frontend bloqueia o salvamento caso a soma divirja.
2.  **Slider do Fator de Amortização:** O administrador ajusta o redutor para CNAEs secundários (de 0% a 100% de peso).
3.  **Tabela de Parametrização de CNAEs:** Campo para cadastrar regras de peso para códigos específicos ou faixas de CNAE (ex: CNAE `4781-4/00` = 40 pontos).
4.  **Botão "Salvar e Recalcular Score da Base":** Inicia o processo em lote.
5.  **Barra de Progresso (UX):** Exibe o percentual de conclusão do recálculo no banco de dados.

---

## 3. Sinais de Contratação (Job Recruiting OSINT)

### Objetivo de negócio
Detectar se a empresa está em processo de expansão ou crescimento físico operacional, identificando se ela possui vagas abertas na área logística.

### Regras de Negócio
- **Busca Automatizada de Vagas:** Utilizando o motor de busca API Serper, o sistema realiza varredura com o nome da empresa em portais públicos de vagas de emprego (LinkedIn Jobs, Indeed, Infojobs).
- **Palavras-chave Qualificadoras:** O robô busca ocorrências nos títulos de vagas:
  - *Área Operacional:* "expedição", "estoque", "auxiliar de logística", "conferente", "embalador".
  - *Área Comercial/Digital:* "gerente de e-commerce", "assistente de e-commerce", "atendimento e-commerce".
- **Sinalização do Alerta:** Se vagas vigentes forem encontradas nos últimos 60 dias, o sistema insere o selo `Contratando Logística 📦` no card do cliente. Ao clicar, o vendedor visualiza a vaga encontrada como argumento para a sua abordagem comercial.

---

## 4. Alerta de Novas Empresas (Gatilho da Receita Federal)

### Objetivo de negócio
Permitir que a força de vendas dos Correios capte clientes recém-nascidos antes das transportadoras privadas.

### Regras de Negócio
- **Inserção de Novos Leads:** O administrador ou script cron de ingestão insere novos CNPJs gerados pela Receita Federal pertencentes à lotação regional da Superintendência Estadual (SE) do vendedor.
- **Filtro de Relevância:** Apenas empresas cadastradas com CNAEs de comércio varejista (Score 10) que tenham menos de 6 meses de abertura serão marcadas com a tag `Nova Empresa 🌱`.
- **Priorização Regional:** Essas empresas são sugeridas como "Alvos Recomendados" no dashboard do vendedor.

---

## 5. Algoritmo de Clientes Gêmeos (Lookalike)

### Objetivo de negócio
Descobrir novas contas promissoras com base no perfil de faturamento dos melhores clientes atuais da casa.

### Regras de Negócio
- **Definição de Perfil Campeão:** O administrador no painel gerencial visualiza as faixas de capital social e os CNAEs dos clientes que faturam as maiores faixas de contratos (PAC/SEDEX) ativos.
- **Mapeamento de Oportunidades:** O sistema busca no banco de dados geral de CNPJs do estado empresas que tenham características idênticas (ex: mesma cidade, faixa de capital social e CNAE semelhante) mas que ainda constem como "Clientes Livres" (sem vendedor atrelado).
- **Delegação:** O coordenador ou administrador pode, no painel, sugerir esses "leads gêmeos" diretamente para a carteira dos vendedores mais próximos do local.

---

## 6. Prospecção por Logística Reversa e Encomendas (Reclame Aqui OSINT)

### Objetivo de negócio
Identificar se uma empresa utiliza serviços postais (envio de encomendas, logística reversa, frete) rastreando reclamações públicas no portal Reclame Aqui. Empresas que possuem reclamações sobre "atraso na entrega", "frete" ou "logística reversa" são leads qualificados altíssimos para os Correios, pois já têm volume de postagem.

### Regras de Negócio
- **Busca Automatizada via Serper API:** O sistema utiliza a API Serper para realizar uma busca estruturada no Google restrita ao domínio do Reclame Aqui (ex: `site:reclameaqui.com.br "Nome Fantasia da Empresa" (frete OR postal OR sedex OR pac OR encomenda OR "logística reversa")`).
- **Gatilho de Execução:** Esta busca pode ser disparada manualmente pelo administrador no painel para um CNPJ específico ou em lote para leads de alto potencial.
- **Identificação e Pontuação:**
  - Se resultados forem encontrados nos últimos 12 meses, a empresa ganha uma pontuação adicional no seu Score Preditivo.
  - O sistema insere um badge `Volume Postal Detectado 📦` ou `Logística Reversa 🔄` no card do cliente.
- **Painel Administrativo:** O perfil Admin terá uma interface dedicada para inserir um CNPJ (ou buscar da base) e disparar o "Scanner Reclame Aqui". O resultado mostrará os links das reclamações encontradas, servindo como argumento de vendas (ex: "Vi que seus clientes estão reclamando de atrasos no frete, os Correios podem ajudar...").
