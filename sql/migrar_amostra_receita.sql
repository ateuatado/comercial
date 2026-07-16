-- ============================================================
-- SPIV — Migração de Amostra: receita -> spivvps
-- ============================================================
-- Objetivo: Copiar 100.000 CNPJs aleatórios do banco local
--           (schema receita) para o banco spivvps.
--
-- Como usar (execute conectado ao banco DESTINO: spivvps):
--
--   psql -U postgres -d spivvps -f migrar_amostra_receita.sql
--
-- O script usa postgres_fdw para ler do banco ORIGEM (spiv/receita).
-- Ajuste os parâmetros de conexão abaixo se necessário.
-- ============================================================

\echo '=== SPIV — Migração de amostra receita -> spivvps ==='
\echo ''
\timing on

-- ────────────────────────────────────────────────────────────
-- 1. EXTENSÃO E FOREIGN SERVER
--    Permite que spivvps leia diretamente do banco de origem
-- ────────────────────────────────────────────────────────────
CREATE EXTENSION IF NOT EXISTS postgres_fdw;

-- Remove servidor anterior se existir (idempotente)
DROP SERVER IF EXISTS origem_receita CASCADE;

-- Cria o servidor apontando para o banco de ORIGEM
-- Ajuste host/port/dbname conforme seu ambiente local
CREATE SERVER origem_receita
    FOREIGN DATA WRAPPER postgres_fdw
    OPTIONS (host 'localhost', port '5432', dbname 'spiv');
    -- Se o banco de origem tiver outro nome, ajuste 'dbname' acima

-- Mapeamento de usuário: conecta ao banco origem como postgres
CREATE USER MAPPING IF NOT EXISTS FOR postgres
    SERVER origem_receita
    OPTIONS (user 'postgres', password 'LulaTetra26');

-- ────────────────────────────────────────────────────────────
-- 2. SCHEMA DE DESTINO
-- ────────────────────────────────────────────────────────────
CREATE SCHEMA IF NOT EXISTS receita;

-- ────────────────────────────────────────────────────────────
-- 3. TABELAS DE DOMÍNIO (lookup — copiadas integralmente)
--    São pequenas (~centenas de linhas) e necessárias para
--    as views e joins do sistema.
-- ────────────────────────────────────────────────────────────
\echo '--- Criando tabelas de domínio...'

CREATE TABLE IF NOT EXISTS receita.cnaes (
    codigo    VARCHAR(7)   NOT NULL,
    descricao VARCHAR(250) NOT NULL,
    CONSTRAINT pk_cnaes PRIMARY KEY (codigo)
);

CREATE TABLE IF NOT EXISTS receita.motivos (
    codigo    VARCHAR(2)   NOT NULL,
    descricao VARCHAR(250) NOT NULL,
    CONSTRAINT pk_motivos PRIMARY KEY (codigo)
);

CREATE TABLE IF NOT EXISTS receita.municipios (
    codigo    VARCHAR(4)   NOT NULL,
    descricao VARCHAR(250) NOT NULL,
    CONSTRAINT pk_municipios PRIMARY KEY (codigo)
);

CREATE TABLE IF NOT EXISTS receita.naturezas (
    codigo    VARCHAR(4)   NOT NULL,
    descricao VARCHAR(250) NOT NULL,
    CONSTRAINT pk_naturezas PRIMARY KEY (codigo)
);

CREATE TABLE IF NOT EXISTS receita.paises (
    codigo    VARCHAR(3)   NOT NULL,
    descricao VARCHAR(250) NOT NULL,
    CONSTRAINT pk_paises PRIMARY KEY (codigo)
);

CREATE TABLE IF NOT EXISTS receita.qualificacoes (
    codigo    VARCHAR(2)   NOT NULL,
    descricao VARCHAR(250) NOT NULL,
    CONSTRAINT pk_qualificacoes PRIMARY KEY (codigo)
);

-- ────────────────────────────────────────────────────────────
-- 4. TABELAS PRINCIPAIS (estrutura)
-- ────────────────────────────────────────────────────────────
\echo '--- Criando tabelas principais...'

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

-- ────────────────────────────────────────────────────────────
-- 5. TABELAS ESTRANGEIRAS (leitura da origem via FDW)
-- ────────────────────────────────────────────────────────────
\echo '--- Importando schema estrangeiro...'

-- Importa automaticamente todas as tabelas do schema receita da origem
-- Ficam disponíveis temporariamente como receita_fdw.*
CREATE SCHEMA IF NOT EXISTS receita_fdw;

IMPORT FOREIGN SCHEMA receita
    FROM SERVER origem_receita
    INTO receita_fdw;

-- ────────────────────────────────────────────────────────────
-- 6. COPIAR TABELAS DE DOMÍNIO (integrais)
-- ────────────────────────────────────────────────────────────
\echo '--- Copiando domínios (lookup tables)...'

INSERT INTO receita.cnaes          SELECT * FROM receita_fdw.cnaes          ON CONFLICT DO NOTHING;
INSERT INTO receita.motivos        SELECT * FROM receita_fdw.motivos        ON CONFLICT DO NOTHING;
INSERT INTO receita.municipios     SELECT * FROM receita_fdw.municipios     ON CONFLICT DO NOTHING;
INSERT INTO receita.naturezas      SELECT * FROM receita_fdw.naturezas      ON CONFLICT DO NOTHING;
INSERT INTO receita.paises         SELECT * FROM receita_fdw.paises         ON CONFLICT DO NOTHING;
INSERT INTO receita.qualificacoes  SELECT * FROM receita_fdw.qualificacoes  ON CONFLICT DO NOTHING;

-- ────────────────────────────────────────────────────────────
-- 7. SELECIONAR 100K CNPJs ALEATÓRIOS E COPIAR DADOS
-- ────────────────────────────────────────────────────────────
\echo '--- Selecionando 100.000 CNPJs aleatórios...'

-- Tabela temporária com a amostra de cnpj_basico
-- Usamos TABLESAMPLE BERNOULLI para ser mais rápido que ORDER BY RANDOM()
-- em tabelas gigantes (evita full sort)
CREATE TEMP TABLE amostra_cnpjs AS
SELECT cnpj_basico
FROM receita_fdw.empresas
TABLESAMPLE BERNOULLI(5)   -- ~5% da tabela; ajuste se necessário
LIMIT 100000;

-- Garante o índice para os JOINs abaixo
CREATE INDEX ON amostra_cnpjs (cnpj_basico);

\echo '--- Copiando empresas...'
INSERT INTO receita.empresas
SELECT e.*
FROM receita_fdw.empresas e
INNER JOIN amostra_cnpjs a ON a.cnpj_basico = e.cnpj_basico
ON CONFLICT DO NOTHING;

\echo '--- Copiando estabelecimentos...'
INSERT INTO receita.estabelecimentos
SELECT est.*
FROM receita_fdw.estabelecimentos est
INNER JOIN amostra_cnpjs a ON a.cnpj_basico = est.cnpj_basico
ON CONFLICT DO NOTHING;

\echo '--- Copiando sócios...'
INSERT INTO receita.socios (
    cnpj_basico, identificador_socio, nome_socio, cpf_cnpj_socio,
    qualificacao_socio, data_entrada_sociedade, pais, representante_legal,
    nome_representante, qualificacao_representante, faixa_etaria,
    created_at, updated_at
)
SELECT
    sc.cnpj_basico, sc.identificador_socio, sc.nome_socio, sc.cpf_cnpj_socio,
    sc.qualificacao_socio, sc.data_entrada_sociedade, sc.pais, sc.representante_legal,
    sc.nome_representante, sc.qualificacao_representante, sc.faixa_etaria,
    sc.created_at, sc.updated_at
FROM receita_fdw.socios sc
INNER JOIN amostra_cnpjs a ON a.cnpj_basico = sc.cnpj_basico
ON CONFLICT DO NOTHING;

\echo '--- Copiando simples nacional...'
INSERT INTO receita.simples
SELECT s.*
FROM receita_fdw.simples s
INNER JOIN amostra_cnpjs a ON a.cnpj_basico = s.cnpj_basico
ON CONFLICT DO NOTHING;

-- ────────────────────────────────────────────────────────────
-- 8. ÍNDICES DE PERFORMANCE
-- ────────────────────────────────────────────────────────────
\echo '--- Criando índices...'

CREATE INDEX IF NOT EXISTS idx_empresas_natureza   ON receita.empresas (natureza_juridica);
CREATE INDEX IF NOT EXISTS idx_empresas_porte      ON receita.empresas (porte_empresa);
CREATE INDEX IF NOT EXISTS idx_estab_cnpj_basico   ON receita.estabelecimentos (cnpj_basico);
CREATE INDEX IF NOT EXISTS idx_estab_uf            ON receita.estabelecimentos (uf);
CREATE INDEX IF NOT EXISTS idx_estab_municipio     ON receita.estabelecimentos (municipio);
CREATE INDEX IF NOT EXISTS idx_estab_cnae          ON receita.estabelecimentos (cnae_fiscal_principal);
CREATE INDEX IF NOT EXISTS idx_estab_situacao      ON receita.estabelecimentos (situacao_cadastral);
CREATE INDEX IF NOT EXISTS idx_socios_cnpj_basico  ON receita.socios (cnpj_basico);
CREATE INDEX IF NOT EXISTS idx_simples_opcao       ON receita.simples (opcao_simples);

-- ────────────────────────────────────────────────────────────
-- 9. LIMPEZA: remove FDW (não é mais necessário)
-- ────────────────────────────────────────────────────────────
\echo '--- Limpando objetos temporários FDW...'
DROP SCHEMA receita_fdw CASCADE;
DROP SERVER IF EXISTS origem_receita CASCADE;

-- ────────────────────────────────────────────────────────────
-- 10. RESUMO
-- ────────────────────────────────────────────────────────────
\echo ''
\echo '=== Contagens finais ==='
SELECT 'empresas'        AS tabela, COUNT(*) AS registros FROM receita.empresas
UNION ALL
SELECT 'estabelecimentos',          COUNT(*) FROM receita.estabelecimentos
UNION ALL
SELECT 'socios',                    COUNT(*) FROM receita.socios
UNION ALL
SELECT 'simples',                   COUNT(*) FROM receita.simples
UNION ALL
SELECT 'cnaes',                     COUNT(*) FROM receita.cnaes
UNION ALL
SELECT 'municipios',                COUNT(*) FROM receita.municipios;

\echo ''
\echo '=== Migração concluída! ==='
