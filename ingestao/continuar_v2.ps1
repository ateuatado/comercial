# ============================================================
# Continuação v2 - Estabelecimentos, Sócios, Simples
# Com otimização: DROP índices GIN antes do INSERT, recria depois
# ============================================================
param(
    [string]$TempDir  = "c:\xampp\htdocs\spiv\adm\temp_extract",
    [string]$PgHost   = "localhost",
    [string]$PgPort   = "5432",
    [string]$PgDb     = "spiv",
    [string]$PgUser   = "postgres",
    [string]$PgPass   = "LulaTetra26",
    [string]$PsqlExe  = "C:\Program Files\PostgreSQL\18\bin\psql.exe"
)

$ErrorActionPreference = "Stop"
$env:PGPASSWORD = $PgPass
$env:PGCLIENTENCODING = "UTF8"

function Write-Log {
    param([string]$Msg, [string]$Level = "INFO")
    $ts = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
    $line = "[$ts] [$Level] $Msg"
    Write-Host $line
    Add-Content -Path "$PSScriptRoot\ingestao.log" -Value $line -Encoding UTF8
}

function Run-Psql {
    param([string]$Sql, [switch]$File)
    $prevPref = $ErrorActionPreference
    $ErrorActionPreference = "Continue"
    if ($File) {
        & $PsqlExe -h $PgHost -p $PgPort -U $PgUser -d $PgDb -f $Sql -v ON_ERROR_STOP=1 2>&1 | ForEach-Object {
            if ($_ -is [System.Management.Automation.ErrorRecord]) {
                $msg = $_.ToString()
                if ($msg -notmatch 'NOTA:|NOTICE:|INFO:') { Write-Host "PSQL STDERR: $msg" }
            } else { Write-Host $_ }
        }
    } else {
        & $PsqlExe -h $PgHost -p $PgPort -U $PgUser -d $PgDb -c $Sql 2>&1 | ForEach-Object {
            if ($_ -is [System.Management.Automation.ErrorRecord]) {
                $msg = $_.ToString()
                if ($msg -notmatch 'NOTA:|NOTICE:|INFO:') { Write-Host "PSQL STDERR: $msg" }
            } else { Write-Host $_ }
        }
    }
    $ErrorActionPreference = $prevPref
    if ($LASTEXITCODE -ne 0) { throw "Erro psql (exit $LASTEXITCODE)" }
}

function Run-Copy {
    param([string]$Table, [string]$Columns, [string]$FilePath)
    $tmpSql = Join-Path $TempDir "_copy_cmd.sql"
    $copyLine = "\copy $Table ($Columns) FROM '$FilePath' WITH (FORMAT csv, DELIMITER ';', QUOTE '""', ENCODING 'LATIN1')"
    Set-Content -Path $tmpSql -Value $copyLine -Encoding UTF8 -NoNewline
    Run-Psql -Sql $tmpSql -File
}

function Get-TableCount {
    param([string]$Table)
    $result = & $PsqlExe -h $PgHost -p $PgPort -U $PgUser -d $PgDb -t -A -c "SELECT count(*) FROM receita.$Table;" 2>$null
    return [long]$result.Trim()
}

function Log-Ingestao {
    param([string]$Tabela, [long]$Antes, [long]$Novos, [long]$Total, [double]$Duracao, [string]$Status = "concluido")
    $sql = "INSERT INTO receita.log_ingestao (tabela, registros_antes, registros_novos, registros_total, duracao_seg, status) VALUES ('$Tabela', $Antes, $Novos, $Total, $Duracao, '$Status');"
    Run-Psql $sql
}

Write-Log "========== CONTINUAÇÃO v2 =========="

# ── Cancelar queries ativas remanescentes ────────────────────
Write-Log "Cancelando queries ativas..."
Run-Psql "SELECT pg_terminate_backend(pid) FROM pg_stat_activity WHERE datname = 'spiv' AND state = 'active' AND pid <> pg_backend_pid() AND query LIKE 'INSERT INTO receita%';"
Run-Psql "SELECT pg_terminate_backend(pid) FROM pg_stat_activity WHERE datname = 'spiv' AND query LIKE 'autovacuum%';"
Start-Sleep -Seconds 3

# ── Limpar staging remanescente ──────────────────────────────
Run-Psql "DROP TABLE IF EXISTS receita.stg_estabelecimentos; DROP TABLE IF EXISTS receita.stg_socios; DROP TABLE IF EXISTS receita.stg_simples;"

# ── Verificar estado atual ───────────────────────────────────
$countEstab = 0; try { $countEstab = Get-TableCount "estabelecimentos" } catch {}
$countSocios = 0; try { $countSocios = Get-TableCount "socios" } catch {}
$countSimples = 0; try { $countSimples = Get-TableCount "simples" } catch {}
Write-Log "Estado atual: estabelecimentos=$countEstab socios=$countSocios simples=$countSimples"

# ══════════════════════════════════════════════════════════════
# ESTABELECIMENTOS
# ══════════════════════════════════════════════════════════════

if ($countEstab -eq 0) {
    $sw = [System.Diagnostics.Stopwatch]::StartNew()
    Write-Log "=== ESTABELECIMENTOS ==="

    # Dropar índices GIN para acelerar INSERT
    Write-Log "  Dropando índices para INSERT em massa..."
    Run-Psql "DROP INDEX IF EXISTS receita.idx_estab_nome_fantasia;"

    # Criar staging
    Write-Log "  Criando staging..."
    Run-Psql @"
DROP TABLE IF EXISTS receita.stg_estabelecimentos;
CREATE TABLE receita.stg_estabelecimentos (
    cnpj_basico VARCHAR(8), cnpj_ordem VARCHAR(4), cnpj_dv VARCHAR(2),
    identificador_matriz_filial VARCHAR(1), nome_fantasia VARCHAR(200),
    situacao_cadastral VARCHAR(2), data_situacao_cadastral VARCHAR(8),
    motivo_situacao_cadastral VARCHAR(2), nome_cidade_exterior VARCHAR(100),
    pais VARCHAR(3), data_inicio_atividade VARCHAR(8),
    cnae_fiscal_principal VARCHAR(7), cnae_fiscal_secundaria TEXT,
    tipo_logradouro VARCHAR(20), logradouro VARCHAR(200), numero VARCHAR(10),
    complemento VARCHAR(200), bairro VARCHAR(100), cep VARCHAR(8),
    uf VARCHAR(2), municipio VARCHAR(4),
    ddd_1 VARCHAR(4), telefone_1 VARCHAR(10), ddd_2 VARCHAR(4), telefone_2 VARCHAR(10),
    ddd_fax VARCHAR(4), fax VARCHAR(10), email VARCHAR(200),
    situacao_especial VARCHAR(100), data_situacao_especial VARCHAR(8)
);
"@

    $estabCols = "cnpj_basico, cnpj_ordem, cnpj_dv, identificador_matriz_filial, nome_fantasia, situacao_cadastral, data_situacao_cadastral, motivo_situacao_cadastral, nome_cidade_exterior, pais, data_inicio_atividade, cnae_fiscal_principal, cnae_fiscal_secundaria, tipo_logradouro, logradouro, numero, complemento, bairro, cep, uf, municipio, ddd_1, telefone_1, ddd_2, telefone_2, ddd_fax, fax, email, situacao_especial, data_situacao_especial"

    # Listar arquivos CSV de estabelecimentos
    $csvFiles = Get-ChildItem $TempDir -Filter "*.ESTABELE" | Sort-Object Name
    Write-Log "  Encontrados $($csvFiles.Count) arquivos ESTABELE"

    foreach ($f in $csvFiles) {
        $sizeGB = [math]::Round($f.Length/1GB, 1)
        Write-Log "  COPY $($f.Name) (${sizeGB}GB)..."
        Run-Copy -Table "receita.stg_estabelecimentos" -Columns $estabCols -FilePath $f.FullName
    }

    Write-Log "  Deduplicando staging..."
    Run-Psql @"
DELETE FROM receita.stg_estabelecimentos a USING receita.stg_estabelecimentos b
WHERE a.ctid < b.ctid AND a.cnpj_basico = b.cnpj_basico AND a.cnpj_ordem = b.cnpj_ordem AND a.cnpj_dv = b.cnpj_dv;
"@

    Write-Log "  INSERT direto (tabela vazia)..."
    Run-Psql @"
INSERT INTO receita.estabelecimentos ($estabCols)
SELECT $estabCols FROM receita.stg_estabelecimentos;
"@

    Run-Psql "DROP TABLE receita.stg_estabelecimentos;"

    # Recriar índice GIN
    Write-Log "  Recriando índice GIN nome_fantasia..."
    Run-Psql "CREATE INDEX idx_estab_nome_fantasia ON receita.estabelecimentos USING gin (nome_fantasia gin_trgm_ops);"

    $total = Get-TableCount "estabelecimentos"
    $sw.Stop()
    Log-Ingestao -Tabela "estabelecimentos" -Antes 0 -Novos $total -Total $total -Duracao $sw.Elapsed.TotalSeconds
    Write-Log "  ESTABELECIMENTOS: $total registros ($([math]::Round($sw.Elapsed.TotalSeconds/60, 1))min)"
} else {
    Write-Log "  ESTABELECIMENTOS: já carregado ($countEstab registros). Pulando."
}

# ══════════════════════════════════════════════════════════════
# SÓCIOS
# ══════════════════════════════════════════════════════════════

if ($countSocios -eq 0) {
    $sw = [System.Diagnostics.Stopwatch]::StartNew()
    Write-Log "=== SÓCIOS ==="

    # Dropar índice GIN para acelerar INSERT
    Write-Log "  Dropando índices GIN para INSERT em massa..."
    Run-Psql "DROP INDEX IF EXISTS receita.idx_socios_nome;"

    Write-Log "  Criando staging..."
    Run-Psql @"
DROP TABLE IF EXISTS receita.stg_socios;
CREATE TABLE receita.stg_socios (
    cnpj_basico VARCHAR(8), identificador_socio VARCHAR(1),
    nome_socio VARCHAR(200), cpf_cnpj_socio VARCHAR(14),
    qualificacao_socio VARCHAR(2), data_entrada_sociedade VARCHAR(8),
    pais VARCHAR(3), representante_legal VARCHAR(14),
    nome_representante VARCHAR(200), qualificacao_representante VARCHAR(2),
    faixa_etaria VARCHAR(1)
);
"@

    $socioCols = "cnpj_basico, identificador_socio, nome_socio, cpf_cnpj_socio, qualificacao_socio, data_entrada_sociedade, pais, representante_legal, nome_representante, qualificacao_representante, faixa_etaria"

    $csvFiles = Get-ChildItem $TempDir -Filter "*.SOCIOCSV" | Sort-Object Name
    Write-Log "  Encontrados $($csvFiles.Count) arquivos SOCIOCSV"

    foreach ($f in $csvFiles) {
        $sizeMB = [math]::Round($f.Length/1MB, 0)
        Write-Log "  COPY $($f.Name) (${sizeMB}MB)..."
        Run-Copy -Table "receita.stg_socios" -Columns $socioCols -FilePath $f.FullName
    }

    Write-Log "  Deduplicando staging..."
    Run-Psql @"
DELETE FROM receita.stg_socios a USING receita.stg_socios b
WHERE a.ctid < b.ctid AND a.cnpj_basico = b.cnpj_basico AND a.cpf_cnpj_socio = b.cpf_cnpj_socio AND a.qualificacao_socio = b.qualificacao_socio;
"@

    Write-Log "  INSERT direto (tabela vazia, sem índice GIN)..."
    Run-Psql @"
INSERT INTO receita.socios ($socioCols)
SELECT $socioCols FROM receita.stg_socios;
"@

    Run-Psql "DROP TABLE receita.stg_socios;"

    # Recriar índice GIN
    Write-Log "  Recriando índice GIN nome_socio..."
    Run-Psql "CREATE INDEX idx_socios_nome ON receita.socios USING gin (nome_socio gin_trgm_ops);"

    $total = Get-TableCount "socios"
    $sw.Stop()
    Log-Ingestao -Tabela "socios" -Antes 0 -Novos $total -Total $total -Duracao $sw.Elapsed.TotalSeconds
    Write-Log "  SOCIOS: $total registros ($([math]::Round($sw.Elapsed.TotalSeconds/60, 1))min)"
} else {
    Write-Log "  SOCIOS: já carregado ($countSocios registros). Pulando."
}

# ══════════════════════════════════════════════════════════════
# SIMPLES
# ══════════════════════════════════════════════════════════════

if ($countSimples -eq 0) {
    $sw = [System.Diagnostics.Stopwatch]::StartNew()
    Write-Log "=== SIMPLES ==="

    Write-Log "  Criando staging..."
    Run-Psql @"
DROP TABLE IF EXISTS receita.stg_simples;
CREATE TABLE receita.stg_simples (
    cnpj_basico VARCHAR(8), opcao_simples VARCHAR(1),
    data_opcao_simples VARCHAR(8), data_exclusao_simples VARCHAR(8),
    opcao_mei VARCHAR(1), data_opcao_mei VARCHAR(8), data_exclusao_mei VARCHAR(8)
);
"@

    $simplesCols = "cnpj_basico, opcao_simples, data_opcao_simples, data_exclusao_simples, opcao_mei, data_opcao_mei, data_exclusao_mei"

    # Arquivo é F.K03200$W.SIMPLES.CSV.D60613
    $csvFiles = Get-ChildItem $TempDir -Filter "*SIMPLES*" -Exclude "*.zip" | Sort-Object Name
    Write-Log "  Encontrados $($csvFiles.Count) arquivos SIMPLES"

    foreach ($f in $csvFiles) {
        $sizeGB = [math]::Round($f.Length/1GB, 1)
        Write-Log "  COPY $($f.Name) (${sizeGB}GB)..."
        Run-Copy -Table "receita.stg_simples" -Columns $simplesCols -FilePath $f.FullName
    }

    Write-Log "  Deduplicando staging..."
    Run-Psql @"
DELETE FROM receita.stg_simples a USING receita.stg_simples b
WHERE a.ctid < b.ctid AND a.cnpj_basico = b.cnpj_basico;
"@

    Write-Log "  INSERT direto (tabela vazia)..."
    Run-Psql @"
INSERT INTO receita.simples ($simplesCols)
SELECT $simplesCols FROM receita.stg_simples;
"@

    Run-Psql "DROP TABLE receita.stg_simples;"

    $total = Get-TableCount "simples"
    $sw.Stop()
    Log-Ingestao -Tabela "simples" -Antes 0 -Novos $total -Total $total -Duracao $sw.Elapsed.TotalSeconds
    Write-Log "  SIMPLES: $total registros ($([math]::Round($sw.Elapsed.TotalSeconds/60, 1))min)"
} else {
    Write-Log "  SIMPLES: já carregado ($countSimples registros). Pulando."
}

# ── ANALYZE ──────────────────────────────────────────────────
Write-Log "Atualizando estatísticas..."
Run-Psql "ANALYZE receita.empresas; ANALYZE receita.estabelecimentos; ANALYZE receita.socios; ANALYZE receita.simples;"

# ── RESUMO ───────────────────────────────────────────────────
Write-Log "=== RESUMO FINAL ==="
$resumo = & $PsqlExe -h $PgHost -p $PgPort -U $PgUser -d $PgDb -c @"
SELECT 'empresas' AS tabela, count(*) AS registros FROM receita.empresas
UNION ALL SELECT 'estabelecimentos', count(*) FROM receita.estabelecimentos
UNION ALL SELECT 'socios', count(*) FROM receita.socios
UNION ALL SELECT 'simples', count(*) FROM receita.simples
UNION ALL SELECT 'cnaes', count(*) FROM receita.cnaes
UNION ALL SELECT 'motivos', count(*) FROM receita.motivos
UNION ALL SELECT 'municipios', count(*) FROM receita.municipios
UNION ALL SELECT 'naturezas', count(*) FROM receita.naturezas
UNION ALL SELECT 'paises', count(*) FROM receita.paises
UNION ALL SELECT 'qualificacoes', count(*) FROM receita.qualificacoes
ORDER BY tabela;
"@
Write-Host $resumo
Write-Log ($resumo -join "`n")

Write-Log "========== INGESTÃO CONCLUÍDA =========="
