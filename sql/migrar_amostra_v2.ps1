# ============================================================
# SPIV — Migração de Amostra via COPY (muito mais rápido)
# ============================================================
# Exporta 100k CNPJs do banco 'spiv' (receita) para CSV,
# depois importa no banco 'spivvps'.
#
# Pré-requisito: senha do postgres no PGPASSWORD
# ============================================================

$PSQL    = "C:\Program Files\PostgreSQL\18\bin\psql.exe"
$PG_USER = "postgres"
$PG_PASS = "LulaTetra26"
$DB_ORIG = "spiv"
$DB_DEST = "spivvps"
$TMP_DIR = "C:\Temp\spiv_migra"

# Garante pasta temporária
New-Item -ItemType Directory -Force -Path $TMP_DIR | Out-Null

$env:PGPASSWORD = $PG_PASS

Write-Host "=== SPIV — Migração de amostra receita -> spivvps ===" -ForegroundColor Cyan
Write-Host ""

# ────────────────────────────────────────────────────────────
# PASSO 1: Criar schema e tabelas no destino
# ────────────────────────────────────────────────────────────
Write-Host "[1/6] Criando schema receita no spivvps..." -ForegroundColor Yellow

$sql_schema = @"
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
"@

$sql_schema | & $PSQL -U $PG_USER -d $DB_DEST
Write-Host "  OK" -ForegroundColor Green

# ────────────────────────────────────────────────────────────
# PASSO 2: Exportar tabelas de domínio (integrais, são pequenas)
# ────────────────────────────────────────────────────────────
Write-Host "[2/6] Exportando tabelas de domínio..." -ForegroundColor Yellow

foreach ($tbl in @("cnaes","motivos","municipios","naturezas","paises","qualificacoes")) {
    $file = "$TMP_DIR\$tbl.csv"
    & $PSQL -U $PG_USER -d $DB_ORIG -c "\COPY receita.$tbl TO '$file' CSV HEADER"
    Write-Host "  Exportado: $tbl" -ForegroundColor Gray
}

# ────────────────────────────────────────────────────────────
# PASSO 3: Exportar 100k CNPJs aleatórios (TABLESAMPLE é rápido)
# ────────────────────────────────────────────────────────────
Write-Host "[3/6] Exportando amostra de 100k empresas..." -ForegroundColor Yellow

# TABLESAMPLE SYSTEM(x) é MUITO mais rápido que ORDER BY RANDOM()
# Pega ~x% das páginas de disco sem ler toda a tabela
$sql_empresas = @"
\COPY (
    SELECT * FROM receita.empresas
    TABLESAMPLE SYSTEM(2)
    LIMIT 100000
) TO '$TMP_DIR\empresas.csv' CSV HEADER
"@
$sql_empresas | & $PSQL -U $PG_USER -d $DB_ORIG
Write-Host "  OK — empresas.csv" -ForegroundColor Gray

# ────────────────────────────────────────────────────────────
# PASSO 4: Exportar estabelecimentos/sócios/simples dos CNPJs selecionados
# ────────────────────────────────────────────────────────────
Write-Host "[4/6] Exportando estabelecimentos, socios e simples..." -ForegroundColor Yellow

# Primeiro carrega os CNPJs exportados numa tabela temp no banco origem
$sql_temp = @"
CREATE TEMP TABLE _amostra AS
SELECT cnpj_basico FROM receita.empresas TABLESAMPLE SYSTEM(2) LIMIT 100000;
CREATE INDEX ON _amostra(cnpj_basico);

\COPY (SELECT est.* FROM receita.estabelecimentos est
       JOIN _amostra a ON a.cnpj_basico = est.cnpj_basico)
TO '$TMP_DIR\estabelecimentos.csv' CSV HEADER;

\COPY (SELECT sc.* FROM receita.socios sc
       JOIN _amostra a ON a.cnpj_basico = sc.cnpj_basico)
TO '$TMP_DIR\socios.csv' CSV HEADER;

\COPY (SELECT s.* FROM receita.simples s
       JOIN _amostra a ON a.cnpj_basico = s.cnpj_basico)
TO '$TMP_DIR\simples.csv' CSV HEADER;
"@
$sql_temp | & $PSQL -U $PG_USER -d $DB_ORIG
Write-Host "  OK — estabelecimentos, socios, simples" -ForegroundColor Gray

# ────────────────────────────────────────────────────────────
# PASSO 5: Importar tudo no banco destino
# ────────────────────────────────────────────────────────────
Write-Host "[5/6] Importando no spivvps..." -ForegroundColor Yellow

foreach ($tbl in @("cnaes","motivos","municipios","naturezas","paises","qualificacoes")) {
    $file = "$TMP_DIR\$tbl.csv"
    & $PSQL -U $PG_USER -d $DB_DEST -c "\COPY receita.$tbl FROM '$file' CSV HEADER"
    Write-Host "  Importado: $tbl" -ForegroundColor Gray
}

& $PSQL -U $PG_USER -d $DB_DEST -c "\COPY receita.empresas FROM '$TMP_DIR\empresas.csv' CSV HEADER"
Write-Host "  Importado: empresas" -ForegroundColor Gray

& $PSQL -U $PG_USER -d $DB_DEST -c "\COPY receita.estabelecimentos FROM '$TMP_DIR\estabelecimentos.csv' CSV HEADER"
Write-Host "  Importado: estabelecimentos" -ForegroundColor Gray

# socios: pula coluna id (SERIAL, gerado automaticamente)
& $PSQL -U $PG_USER -d $DB_DEST -c @"
\COPY receita.socios(cnpj_basico, identificador_socio, nome_socio,
    cpf_cnpj_socio, qualificacao_socio, data_entrada_sociedade, pais,
    representante_legal, nome_representante, qualificacao_representante,
    faixa_etaria, created_at, updated_at)
FROM '$TMP_DIR\socios.csv' CSV HEADER
"@
Write-Host "  Importado: socios" -ForegroundColor Gray

& $PSQL -U $PG_USER -d $DB_DEST -c "\COPY receita.simples FROM '$TMP_DIR\simples.csv' CSV HEADER"
Write-Host "  Importado: simples" -ForegroundColor Gray

# ────────────────────────────────────────────────────────────
# PASSO 6: Índices e resumo
# ────────────────────────────────────────────────────────────
Write-Host "[6/6] Criando índices e verificando contagens..." -ForegroundColor Yellow

$sql_index = @"
CREATE INDEX IF NOT EXISTS idx_empresas_natureza  ON receita.empresas(natureza_juridica);
CREATE INDEX IF NOT EXISTS idx_empresas_porte     ON receita.empresas(porte_empresa);
CREATE INDEX IF NOT EXISTS idx_estab_cnpj_basico  ON receita.estabelecimentos(cnpj_basico);
CREATE INDEX IF NOT EXISTS idx_estab_uf           ON receita.estabelecimentos(uf);
CREATE INDEX IF NOT EXISTS idx_estab_situacao     ON receita.estabelecimentos(situacao_cadastral);
CREATE INDEX IF NOT EXISTS idx_socios_cnpj_basico ON receita.socios(cnpj_basico);
CREATE INDEX IF NOT EXISTS idx_simples_opcao      ON receita.simples(opcao_simples);

SELECT 'empresas'        AS tabela, COUNT(*) FROM receita.empresas
UNION ALL SELECT 'estabelecimentos', COUNT(*) FROM receita.estabelecimentos
UNION ALL SELECT 'socios',           COUNT(*) FROM receita.socios
UNION ALL SELECT 'simples',          COUNT(*) FROM receita.simples
UNION ALL SELECT 'cnaes',            COUNT(*) FROM receita.cnaes
UNION ALL SELECT 'municipios',       COUNT(*) FROM receita.municipios;
"@
$sql_index | & $PSQL -U $PG_USER -d $DB_DEST

Write-Host ""
Write-Host "=== Migração concluída! ===" -ForegroundColor Cyan
Write-Host "Arquivos temporários em: $TMP_DIR" -ForegroundColor Gray
Write-Host "Pode apagar com: Remove-Item '$TMP_DIR' -Recurse" -ForegroundColor Gray
