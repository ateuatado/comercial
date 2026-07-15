-- ============================================================
-- SPIV — Schema Receita Federal (Dados Abertos de CNPJ)
-- Versão: 1.0
-- Gerado em: 2026-07-01
-- ============================================================
-- Idempotente: pode ser executado múltiplas vezes com segurança.
-- ============================================================

BEGIN;

-- ── Schema ──────────────────────────────────────────────────
CREATE SCHEMA IF NOT EXISTS receita;

-- ============================================================
-- TABELAS DE DOMÍNIO (lookup / referência)
-- ============================================================

CREATE TABLE IF NOT EXISTS receita.cnaes (
    codigo      VARCHAR(7)   NOT NULL,
    descricao   VARCHAR(250) NOT NULL,
    CONSTRAINT pk_cnaes PRIMARY KEY (codigo)
);
COMMENT ON TABLE receita.cnaes IS 'Classificação Nacional de Atividades Econômicas';

CREATE TABLE IF NOT EXISTS receita.motivos (
    codigo      VARCHAR(2)   NOT NULL,
    descricao   VARCHAR(250) NOT NULL,
    CONSTRAINT pk_motivos PRIMARY KEY (codigo)
);
COMMENT ON TABLE receita.motivos IS 'Motivos de situação cadastral';

CREATE TABLE IF NOT EXISTS receita.municipios (
    codigo      VARCHAR(4)   NOT NULL,
    descricao   VARCHAR(250) NOT NULL,
    CONSTRAINT pk_municipios PRIMARY KEY (codigo)
);
COMMENT ON TABLE receita.municipios IS 'Municípios (código Receita Federal)';

CREATE TABLE IF NOT EXISTS receita.naturezas (
    codigo      VARCHAR(4)   NOT NULL,
    descricao   VARCHAR(250) NOT NULL,
    CONSTRAINT pk_naturezas PRIMARY KEY (codigo)
);
COMMENT ON TABLE receita.naturezas IS 'Naturezas jurídicas';

CREATE TABLE IF NOT EXISTS receita.paises (
    codigo      VARCHAR(3)   NOT NULL,
    descricao   VARCHAR(250) NOT NULL,
    CONSTRAINT pk_paises PRIMARY KEY (codigo)
);
COMMENT ON TABLE receita.paises IS 'Países';

CREATE TABLE IF NOT EXISTS receita.qualificacoes (
    codigo      VARCHAR(2)   NOT NULL,
    descricao   VARCHAR(250) NOT NULL,
    CONSTRAINT pk_qualificacoes PRIMARY KEY (codigo)
);
COMMENT ON TABLE receita.qualificacoes IS 'Qualificações de sócios e responsáveis';

-- ============================================================
-- TABELAS DE DADOS PRINCIPAIS
-- ============================================================

-- ── Empresas ────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS receita.empresas (
    cnpj_basico                 VARCHAR(8)     NOT NULL,
    razao_social                VARCHAR(200),
    natureza_juridica           VARCHAR(4),
    qualificacao_responsavel    VARCHAR(2),
    capital_social              VARCHAR(20),
    porte_empresa               VARCHAR(2),
    ente_federativo             VARCHAR(100),
    created_at                  TIMESTAMP      DEFAULT NOW(),
    updated_at                  TIMESTAMP      DEFAULT NOW(),
    CONSTRAINT pk_empresas PRIMARY KEY (cnpj_basico)
);
COMMENT ON TABLE receita.empresas IS 'Dados cadastrais da empresa (CNPJ base)';

-- ── Estabelecimentos ────────────────────────────────────────
CREATE TABLE IF NOT EXISTS receita.estabelecimentos (
    cnpj_basico                 VARCHAR(8)     NOT NULL,
    cnpj_ordem                  VARCHAR(4)     NOT NULL,
    cnpj_dv                     VARCHAR(2)     NOT NULL,
    identificador_matriz_filial VARCHAR(1),
    nome_fantasia               VARCHAR(200),
    situacao_cadastral          VARCHAR(2),
    data_situacao_cadastral     VARCHAR(8),
    motivo_situacao_cadastral   VARCHAR(2),
    nome_cidade_exterior        VARCHAR(100),
    pais                        VARCHAR(3),
    data_inicio_atividade       VARCHAR(8),
    cnae_fiscal_principal       VARCHAR(7),
    cnae_fiscal_secundaria      TEXT,
    tipo_logradouro             VARCHAR(20),
    logradouro                  VARCHAR(200),
    numero                      VARCHAR(10),
    complemento                 VARCHAR(200),
    bairro                      VARCHAR(100),
    cep                         VARCHAR(8),
    uf                          VARCHAR(2),
    municipio                   VARCHAR(4),
    ddd_1                       VARCHAR(4),
    telefone_1                  VARCHAR(10),
    ddd_2                       VARCHAR(4),
    telefone_2                  VARCHAR(10),
    ddd_fax                     VARCHAR(4),
    fax                         VARCHAR(10),
    email                       VARCHAR(200),
    situacao_especial           VARCHAR(100),
    data_situacao_especial      VARCHAR(8),
    created_at                  TIMESTAMP      DEFAULT NOW(),
    updated_at                  TIMESTAMP      DEFAULT NOW(),
    CONSTRAINT pk_estabelecimentos PRIMARY KEY (cnpj_basico, cnpj_ordem, cnpj_dv)
);
COMMENT ON TABLE receita.estabelecimentos IS 'Estabelecimentos (matriz/filiais) de cada empresa';

-- ── Sócios ──────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS receita.socios (
    id                          SERIAL,
    cnpj_basico                 VARCHAR(8)     NOT NULL,
    identificador_socio         VARCHAR(1),
    nome_socio                  VARCHAR(200),
    cpf_cnpj_socio              VARCHAR(14),
    qualificacao_socio          VARCHAR(2),
    data_entrada_sociedade      VARCHAR(8),
    pais                        VARCHAR(3),
    representante_legal         VARCHAR(14),
    nome_representante          VARCHAR(200),
    qualificacao_representante  VARCHAR(2),
    faixa_etaria                VARCHAR(1),
    created_at                  TIMESTAMP      DEFAULT NOW(),
    updated_at                  TIMESTAMP      DEFAULT NOW(),
    CONSTRAINT pk_socios PRIMARY KEY (id),
    CONSTRAINT uq_socios UNIQUE (cnpj_basico, cpf_cnpj_socio, qualificacao_socio)
);
COMMENT ON TABLE receita.socios IS 'Quadro societário das empresas';

-- ── Simples Nacional ────────────────────────────────────────
CREATE TABLE IF NOT EXISTS receita.simples (
    cnpj_basico                 VARCHAR(8)     NOT NULL,
    opcao_simples               VARCHAR(1),
    data_opcao_simples          VARCHAR(8),
    data_exclusao_simples       VARCHAR(8),
    opcao_mei                   VARCHAR(1),
    data_opcao_mei              VARCHAR(8),
    data_exclusao_mei           VARCHAR(8),
    created_at                  TIMESTAMP      DEFAULT NOW(),
    updated_at                  TIMESTAMP      DEFAULT NOW(),
    CONSTRAINT pk_simples PRIMARY KEY (cnpj_basico)
);
COMMENT ON TABLE receita.simples IS 'Opção pelo Simples Nacional e MEI';

-- ============================================================
-- ÍNDICES DE PERFORMANCE
-- ============================================================

-- Empresas
CREATE INDEX IF NOT EXISTS idx_empresas_natureza     ON receita.empresas (natureza_juridica);
CREATE INDEX IF NOT EXISTS idx_empresas_porte        ON receita.empresas (porte_empresa);

-- Estabelecimentos
CREATE INDEX IF NOT EXISTS idx_estab_cnpj_basico     ON receita.estabelecimentos (cnpj_basico);
CREATE INDEX IF NOT EXISTS idx_estab_uf              ON receita.estabelecimentos (uf);
CREATE INDEX IF NOT EXISTS idx_estab_municipio       ON receita.estabelecimentos (municipio);
CREATE INDEX IF NOT EXISTS idx_estab_cnae            ON receita.estabelecimentos (cnae_fiscal_principal);
CREATE INDEX IF NOT EXISTS idx_estab_situacao        ON receita.estabelecimentos (situacao_cadastral);
CREATE INDEX IF NOT EXISTS idx_estab_cep             ON receita.estabelecimentos (cep);
CREATE INDEX IF NOT EXISTS idx_estab_dt_inicio       ON receita.estabelecimentos (data_inicio_atividade);
CREATE INDEX IF NOT EXISTS idx_estab_nome_fantasia   ON receita.estabelecimentos USING gin (nome_fantasia gin_trgm_ops);

-- Sócios
CREATE INDEX IF NOT EXISTS idx_socios_cnpj_basico    ON receita.socios (cnpj_basico);
CREATE INDEX IF NOT EXISTS idx_socios_cpf_cnpj       ON receita.socios (cpf_cnpj_socio);
CREATE INDEX IF NOT EXISTS idx_socios_nome           ON receita.socios USING gin (nome_socio gin_trgm_ops);

-- Simples
CREATE INDEX IF NOT EXISTS idx_simples_opcao         ON receita.simples (opcao_simples);
CREATE INDEX IF NOT EXISTS idx_simples_mei           ON receita.simples (opcao_mei);

-- ============================================================
-- TABELA DE LOG DE INGESTÃO
-- ============================================================

CREATE TABLE IF NOT EXISTS receita.log_ingestao (
    id              SERIAL PRIMARY KEY,
    data_execucao   TIMESTAMP      DEFAULT NOW(),
    data_arquivo    VARCHAR(20),
    tabela          VARCHAR(50)    NOT NULL,
    registros_antes BIGINT         DEFAULT 0,
    registros_novos BIGINT         DEFAULT 0,
    registros_total BIGINT         DEFAULT 0,
    duracao_seg     NUMERIC(10,2),
    status          VARCHAR(20)    DEFAULT 'em_andamento'
);
COMMENT ON TABLE receita.log_ingestao IS 'Histórico de execuções do processo de ingestão mensal';

-- ============================================================
-- VIEW CONSOLIDADA (interface principal para o aplicativo)
-- ============================================================
-- Projetada para ser extensível: basta adicionar JOINs
-- com novas tabelas futuras (ex: leads, carteiras, visitas).
-- ============================================================

DROP VIEW IF EXISTS receita.v_empresas_completa;

CREATE VIEW receita.v_empresas_completa AS
SELECT
    -- ── CNPJ formatado ──────────────────────────────────────
    e.cnpj_basico,
    est.cnpj_ordem,
    est.cnpj_dv,
    e.cnpj_basico || est.cnpj_ordem || est.cnpj_dv
        AS cnpj_completo,
    OVERLAY(
        OVERLAY(
            OVERLAY(
                OVERLAY(
                    e.cnpj_basico || est.cnpj_ordem || est.cnpj_dv
                    PLACING '.' FROM 3 FOR 0
                ) PLACING '.' FROM 7 FOR 0
            ) PLACING '/' FROM 11 FOR 0
        ) PLACING '-' FROM 16 FOR 0
    ) AS cnpj_formatado,

    -- ── Dados da Empresa ────────────────────────────────────
    e.razao_social,
    e.capital_social,
    e.porte_empresa,
    CASE e.porte_empresa
        WHEN '00' THEN 'Não Informado'
        WHEN '01' THEN 'Micro Empresa'
        WHEN '03' THEN 'Empresa de Pequeno Porte'
        WHEN '05' THEN 'Demais'
        ELSE e.porte_empresa
    END AS porte_descricao,
    e.ente_federativo,

    -- ── Natureza Jurídica ───────────────────────────────────
    e.natureza_juridica,
    nat.descricao       AS natureza_descricao,

    -- ── Qualificação do Responsável ─────────────────────────
    e.qualificacao_responsavel,
    qr.descricao        AS qualificacao_resp_descricao,

    -- ── Dados do Estabelecimento ────────────────────────────
    est.identificador_matriz_filial,
    CASE est.identificador_matriz_filial
        WHEN '1' THEN 'Matriz'
        WHEN '2' THEN 'Filial'
        ELSE est.identificador_matriz_filial
    END AS tipo_estabelecimento,
    est.nome_fantasia,

    -- ── Situação Cadastral ──────────────────────────────────
    est.situacao_cadastral,
    CASE est.situacao_cadastral
        WHEN '01' THEN 'Nula'
        WHEN '02' THEN 'Ativa'
        WHEN '03' THEN 'Suspensa'
        WHEN '04' THEN 'Inapta'
        WHEN '08' THEN 'Baixada'
        ELSE est.situacao_cadastral
    END AS situacao_descricao,
    CASE WHEN est.data_situacao_cadastral ~ '^\d{8}$'
         THEN TO_DATE(est.data_situacao_cadastral, 'YYYYMMDD')
         ELSE NULL
    END AS data_situacao_cadastral,

    -- ── Motivo da Situação ──────────────────────────────────
    est.motivo_situacao_cadastral,
    mot.descricao       AS motivo_descricao,

    -- ── Atividade Econômica ─────────────────────────────────
    est.cnae_fiscal_principal,
    cnae.descricao      AS cnae_descricao,
    est.cnae_fiscal_secundaria,

    -- ── Endereço ────────────────────────────────────────────
    est.tipo_logradouro,
    est.logradouro,
    est.numero,
    est.complemento,
    est.bairro,
    est.cep,
    est.uf,
    est.municipio       AS municipio_codigo,
    mun.descricao       AS municipio_nome,
    CONCAT_WS(', ',
        NULLIF(CONCAT_WS(' ', est.tipo_logradouro, est.logradouro), ''),
        NULLIF(est.numero, ''),
        NULLIF(est.complemento, ''),
        NULLIF(est.bairro, ''),
        NULLIF(mun.descricao, ''),
        NULLIF(est.uf, ''),
        NULLIF(est.cep, '')
    ) AS endereco_completo,

    -- ── Contato ─────────────────────────────────────────────
    est.ddd_1,
    est.telefone_1,
    CASE WHEN est.ddd_1 IS NOT NULL AND est.ddd_1 <> ''
         THEN '(' || est.ddd_1 || ') ' || est.telefone_1
         ELSE NULL
    END AS telefone_1_formatado,
    est.ddd_2,
    est.telefone_2,
    est.email,

    -- ── Datas ───────────────────────────────────────────────
    CASE WHEN est.data_inicio_atividade ~ '^\d{8}$'
         THEN TO_DATE(est.data_inicio_atividade, 'YYYYMMDD')
         ELSE NULL
    END AS data_inicio_atividade,

    -- ── País (se exterior) ──────────────────────────────────
    est.pais            AS pais_codigo,
    pai.descricao       AS pais_nome,
    est.nome_cidade_exterior,

    -- ── Simples / MEI ───────────────────────────────────────
    COALESCE(s.opcao_simples, 'N')  AS opcao_simples,
    COALESCE(s.opcao_mei, 'N')      AS opcao_mei,
    CASE WHEN s.data_opcao_simples ~ '^\d{8}$' AND s.data_opcao_simples <> '00000000'
         THEN TO_DATE(s.data_opcao_simples, 'YYYYMMDD')
         ELSE NULL
    END AS data_opcao_simples,
    CASE WHEN s.data_exclusao_simples ~ '^\d{8}$' AND s.data_exclusao_simples <> '00000000'
         THEN TO_DATE(s.data_exclusao_simples, 'YYYYMMDD')
         ELSE NULL
    END AS data_exclusao_simples,

    -- ── Situação Especial ───────────────────────────────────
    est.situacao_especial,
    CASE WHEN est.data_situacao_especial ~ '^\d{8}$'
         THEN TO_DATE(est.data_situacao_especial, 'YYYYMMDD')
         ELSE NULL
    END AS data_situacao_especial,

    -- ── Metadados ───────────────────────────────────────────
    e.created_at        AS empresa_created_at,
    e.updated_at        AS empresa_updated_at,
    est.created_at      AS estab_created_at,
    est.updated_at      AS estab_updated_at

FROM receita.estabelecimentos est

INNER JOIN receita.empresas e
    ON e.cnpj_basico = est.cnpj_basico

LEFT JOIN receita.simples s
    ON s.cnpj_basico = e.cnpj_basico

LEFT JOIN receita.naturezas nat
    ON nat.codigo = e.natureza_juridica

LEFT JOIN receita.qualificacoes qr
    ON qr.codigo = e.qualificacao_responsavel

LEFT JOIN receita.cnaes cnae
    ON cnae.codigo = est.cnae_fiscal_principal

LEFT JOIN receita.motivos mot
    ON mot.codigo = est.motivo_situacao_cadastral

LEFT JOIN receita.municipios mun
    ON mun.codigo = est.municipio

LEFT JOIN receita.paises pai
    ON pai.codigo = est.pais;

COMMENT ON VIEW receita.v_empresas_completa IS
'View consolidada de empresas + estabelecimentos com todos os domínios resolvidos. '
'Interface principal para o aplicativo SPIV. Extensível com novos JOINs.';

-- ============================================================
-- VIEW DE SÓCIOS (complementar)
-- ============================================================

DROP VIEW IF EXISTS receita.v_socios_completa;

CREATE VIEW receita.v_socios_completa AS
SELECT
    sc.id,
    sc.cnpj_basico,
    e.razao_social,
    sc.identificador_socio,
    CASE sc.identificador_socio
        WHEN '1' THEN 'Pessoa Jurídica'
        WHEN '2' THEN 'Pessoa Física'
        WHEN '3' THEN 'Estrangeiro'
        ELSE sc.identificador_socio
    END AS tipo_socio,
    sc.nome_socio,
    sc.cpf_cnpj_socio,
    sc.qualificacao_socio,
    qs.descricao        AS qualificacao_descricao,
    CASE WHEN sc.data_entrada_sociedade ~ '^\d{8}$'
         THEN TO_DATE(sc.data_entrada_sociedade, 'YYYYMMDD')
         ELSE NULL
    END AS data_entrada_sociedade,
    sc.pais             AS pais_codigo,
    pai.descricao       AS pais_nome,
    sc.representante_legal,
    sc.nome_representante,
    sc.qualificacao_representante,
    qrep.descricao      AS qualif_representante_descricao,
    sc.faixa_etaria,
    CASE sc.faixa_etaria
        WHEN '0' THEN 'Não se aplica'
        WHEN '1' THEN '0-12 anos'
        WHEN '2' THEN '13-20 anos'
        WHEN '3' THEN '21-30 anos'
        WHEN '4' THEN '31-40 anos'
        WHEN '5' THEN '41-50 anos'
        WHEN '6' THEN '51-60 anos'
        WHEN '7' THEN '61-70 anos'
        WHEN '8' THEN '71-80 anos'
        WHEN '9' THEN 'Maiores de 80 anos'
        ELSE sc.faixa_etaria
    END AS faixa_etaria_descricao
FROM receita.socios sc

INNER JOIN receita.empresas e
    ON e.cnpj_basico = sc.cnpj_basico

LEFT JOIN receita.qualificacoes qs
    ON qs.codigo = sc.qualificacao_socio

LEFT JOIN receita.qualificacoes qrep
    ON qrep.codigo = sc.qualificacao_representante

LEFT JOIN receita.paises pai
    ON pai.codigo = sc.pais;

COMMENT ON VIEW receita.v_socios_completa IS
'View consolidada do quadro societário com domínios resolvidos.';

COMMIT;
