-- Cria schema e tabelas no banco spivvps (destino)
CREATE SCHEMA IF NOT EXISTS receita;

CREATE TABLE IF NOT EXISTS receita.cnaes (
    codigo VARCHAR(7) NOT NULL, descricao VARCHAR(250) NOT NULL,
    CONSTRAINT pk_cnaes PRIMARY KEY (codigo));

CREATE TABLE IF NOT EXISTS receita.motivos (
    codigo VARCHAR(2) NOT NULL, descricao VARCHAR(250) NOT NULL,
    CONSTRAINT pk_motivos PRIMARY KEY (codigo));

CREATE TABLE IF NOT EXISTS receita.municipios (
    codigo VARCHAR(4) NOT NULL, descricao VARCHAR(250) NOT NULL,
    CONSTRAINT pk_municipios PRIMARY KEY (codigo));

CREATE TABLE IF NOT EXISTS receita.naturezas (
    codigo VARCHAR(4) NOT NULL, descricao VARCHAR(250) NOT NULL,
    CONSTRAINT pk_naturezas PRIMARY KEY (codigo));

CREATE TABLE IF NOT EXISTS receita.paises (
    codigo VARCHAR(3) NOT NULL, descricao VARCHAR(250) NOT NULL,
    CONSTRAINT pk_paises PRIMARY KEY (codigo));

CREATE TABLE IF NOT EXISTS receita.qualificacoes (
    codigo VARCHAR(2) NOT NULL, descricao VARCHAR(250) NOT NULL,
    CONSTRAINT pk_qualificacoes PRIMARY KEY (codigo));

CREATE TABLE IF NOT EXISTS receita.empresas (
    cnpj_basico VARCHAR(8) NOT NULL, razao_social VARCHAR(200),
    natureza_juridica VARCHAR(4), qualificacao_responsavel VARCHAR(2),
    capital_social VARCHAR(20), porte_empresa VARCHAR(2),
    ente_federativo VARCHAR(100), created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW(),
    CONSTRAINT pk_empresas PRIMARY KEY (cnpj_basico));

CREATE TABLE IF NOT EXISTS receita.estabelecimentos (
    cnpj_basico VARCHAR(8) NOT NULL, cnpj_ordem VARCHAR(4) NOT NULL,
    cnpj_dv VARCHAR(2) NOT NULL, identificador_matriz_filial VARCHAR(1),
    nome_fantasia VARCHAR(200), situacao_cadastral VARCHAR(2),
    data_situacao_cadastral VARCHAR(8), motivo_situacao_cadastral VARCHAR(2),
    nome_cidade_exterior VARCHAR(100), pais VARCHAR(3),
    data_inicio_atividade VARCHAR(8), cnae_fiscal_principal VARCHAR(7),
    cnae_fiscal_secundaria TEXT, tipo_logradouro VARCHAR(20),
    logradouro VARCHAR(200), numero VARCHAR(10), complemento VARCHAR(200),
    bairro VARCHAR(100), cep VARCHAR(8), uf VARCHAR(2), municipio VARCHAR(4),
    ddd_1 VARCHAR(4), telefone_1 VARCHAR(10), ddd_2 VARCHAR(4),
    telefone_2 VARCHAR(10), ddd_fax VARCHAR(4), fax VARCHAR(10),
    email VARCHAR(200), situacao_especial VARCHAR(100),
    data_situacao_especial VARCHAR(8), created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW(),
    CONSTRAINT pk_estabelecimentos PRIMARY KEY (cnpj_basico, cnpj_ordem, cnpj_dv));

CREATE TABLE IF NOT EXISTS receita.socios (
    id SERIAL, cnpj_basico VARCHAR(8) NOT NULL,
    identificador_socio VARCHAR(1), nome_socio VARCHAR(200),
    cpf_cnpj_socio VARCHAR(14), qualificacao_socio VARCHAR(2),
    data_entrada_sociedade VARCHAR(8), pais VARCHAR(3),
    representante_legal VARCHAR(14), nome_representante VARCHAR(200),
    qualificacao_representante VARCHAR(2), faixa_etaria VARCHAR(1),
    created_at TIMESTAMP DEFAULT NOW(), updated_at TIMESTAMP DEFAULT NOW(),
    CONSTRAINT pk_socios PRIMARY KEY (id));

CREATE TABLE IF NOT EXISTS receita.simples (
    cnpj_basico VARCHAR(8) NOT NULL, opcao_simples VARCHAR(1),
    data_opcao_simples VARCHAR(8), data_exclusao_simples VARCHAR(8),
    opcao_mei VARCHAR(1), data_opcao_mei VARCHAR(8),
    data_exclusao_mei VARCHAR(8), created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW(),
    CONSTRAINT pk_simples PRIMARY KEY (cnpj_basico));
