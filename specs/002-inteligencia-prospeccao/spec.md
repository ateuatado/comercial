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

## 2. Score de Propensão Logística por CNAEs

### Objetivo de negócio
Classificar e ordenar a lista de clientes do vendedor com base na real probabilidade de a empresa gerar volume de remessas e precisar de contratos com os Correios.

### Regras de Negócio
- **Mapeamento de Relevância por CNAEs:**
  - **Score 10 (Alta Propensão):** CNAEs do comércio varejista de roupas, calçados, cosméticos, eletrônicos, medicamentos, suplementos, e CNAEs declarados de comércio eletrônico direto.
  - **Score 5 (Média Propensão):** Distribuidoras B2B, indústrias leves de confecção, manufatura de bens de consumo físicos de pequeno porte.
  - **Score 1 (Baixa Propensão):** Empresas de serviços puros (consultorias, advocacia, contabilidade, empresas de engenharia, imobiliárias, construção civil).
- **Cálculo do Score Final:** O sistema analisa o CNAE Principal e os CNAEs Secundários obtidos do cadastro da empresa na Receita Federal. O Score de Propensão do cliente será definido pelo **maior score individual** entre o CNAE principal e os secundários (ou seja, se o principal for serviço, mas um dos secundários for varejo de roupas, a pontuação sobe para 10).
- **Ordenação Padrão:** A lista de clientes "Ver meus clientes" e a busca de prospecção regional no celular do vendedor serão ordenadas por padrão em ordem decrescente de Score de Propensão.

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
