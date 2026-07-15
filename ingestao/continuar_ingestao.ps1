# ============================================================
# Continuação da ingestão - Estabelecimentos, Sócios, Simples
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

# ── ESTABELECIMENTOS ─────────────────────────────────────────

function Load-Estabelecimentos {
    $sw = [System.Diagnostics.Stopwatch]::StartNew()
    $antes = 0
    try { $antes = Get-TableCount "estabelecimentos" } catch {}

    Write-Log "  Criando staging estabelecimentos..."
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

    $csvFiles = Get-ChildItem $TempDir -Filter "*ESTABELE*" -Exclude "*.zip" | Sort-Object Name
    foreach ($f in $csvFiles) {
        Write-Log "  COPY $($f.Name)..."
        Run-Copy -Table "receita.stg_estabelecimentos" -Columns $estabCols -FilePath $f.FullName
    }

    Write-Log "  Deduplicando staging estabelecimentos..."
    Run-Psql @"
DELETE FROM receita.stg_estabelecimentos a USING receita.stg_estabelecimentos b
WHERE a.ctid < b.ctid AND a.cnpj_basico = b.cnpj_basico AND a.cnpj_ordem = b.cnpj_ordem AND a.cnpj_dv = b.cnpj_dv;
"@

    if ($antes -eq 0) {
        Write-Log "  INSERT direto estabelecimentos (tabela vazia)..."
        Run-Psql @"
INSERT INTO receita.estabelecimentos ($estabCols)
SELECT $estabCols FROM receita.stg_estabelecimentos;
"@
    } else {
        Write-Log "  UPSERT estabelecimentos (incremental)..."
        Run-Psql @"
INSERT INTO receita.estabelecimentos ($estabCols)
SELECT $estabCols FROM receita.stg_estabelecimentos
ON CONFLICT (cnpj_basico, cnpj_ordem, cnpj_dv) DO UPDATE SET
    identificador_matriz_filial = EXCLUDED.identificador_matriz_filial,
    nome_fantasia = EXCLUDED.nome_fantasia,
    situacao_cadastral = EXCLUDED.situacao_cadastral,
    data_situacao_cadastral = EXCLUDED.data_situacao_cadastral,
    motivo_situacao_cadastral = EXCLUDED.motivo_situacao_cadastral,
    nome_cidade_exterior = EXCLUDED.nome_cidade_exterior,
    pais = EXCLUDED.pais,
    data_inicio_atividade = EXCLUDED.data_inicio_atividade,
    cnae_fiscal_principal = EXCLUDED.cnae_fiscal_principal,
    cnae_fiscal_secundaria = EXCLUDED.cnae_fiscal_secundaria,
    tipo_logradouro = EXCLUDED.tipo_logradouro,
    logradouro = EXCLUDED.logradouro,
    numero = EXCLUDED.numero,
    complemento = EXCLUDED.complemento,
    bairro = EXCLUDED.bairro,
    cep = EXCLUDED.cep, uf = EXCLUDED.uf, municipio = EXCLUDED.municipio,
    ddd_1 = EXCLUDED.ddd_1, telefone_1 = EXCLUDED.telefone_1,
    ddd_2 = EXCLUDED.ddd_2, telefone_2 = EXCLUDED.telefone_2,
    ddd_fax = EXCLUDED.ddd_fax, fax = EXCLUDED.fax,
    email = EXCLUDED.email,
    situacao_especial = EXCLUDED.situacao_especial,
    data_situacao_especial = EXCLUDED.data_situacao_especial,
    updated_at = NOW();
"@
    }

    Run-Psql "DROP TABLE receita.stg_estabelecimentos;"
    $total = Get-TableCount "estabelecimentos"
    $sw.Stop()
    $novos = $total - $antes; if ($novos -lt 0) { $novos = 0 }
    Log-Ingestao -Tabela "estabelecimentos" -Antes $antes -Novos $novos -Total $total -Duracao $sw.Elapsed.TotalSeconds
    Write-Log "  ESTABELECIMENTOS: antes=$antes novos=$novos total=$total ($([math]::Round($sw.Elapsed.TotalSeconds, 1))s)"
}

# ── SÓCIOS ───────────────────────────────────────────────────

function Load-Socios {
    $sw = [System.Diagnostics.Stopwatch]::StartNew()
    $antes = 0
    try { $antes = Get-TableCount "socios" } catch {}

    Write-Log "  Criando staging socios..."
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

    $csvFiles = Get-ChildItem $TempDir -Filter "*SOCIOCSV" | Sort-Object Name
    foreach ($f in $csvFiles) {
        Write-Log "  COPY $($f.Name)..."
        Run-Copy -Table "receita.stg_socios" -Columns $socioCols -FilePath $f.FullName
    }

    Write-Log "  Deduplicando staging socios..."
    Run-Psql @"
DELETE FROM receita.stg_socios a USING receita.stg_socios b
WHERE a.ctid < b.ctid AND a.cnpj_basico = b.cnpj_basico AND a.cpf_cnpj_socio = b.cpf_cnpj_socio AND a.qualificacao_socio = b.qualificacao_socio;
"@

    if ($antes -eq 0) {
        Write-Log "  INSERT direto socios (tabela vazia)..."
        Run-Psql @"
INSERT INTO receita.socios ($socioCols)
SELECT $socioCols FROM receita.stg_socios;
"@
    } else {
        Write-Log "  UPSERT socios (incremental)..."
        Run-Psql @"
INSERT INTO receita.socios ($socioCols)
SELECT $socioCols FROM receita.stg_socios
ON CONFLICT (cnpj_basico, cpf_cnpj_socio, qualificacao_socio) DO UPDATE SET
    identificador_socio = EXCLUDED.identificador_socio,
    nome_socio = EXCLUDED.nome_socio,
    data_entrada_sociedade = EXCLUDED.data_entrada_sociedade,
    pais = EXCLUDED.pais,
    representante_legal = EXCLUDED.representante_legal,
    nome_representante = EXCLUDED.nome_representante,
    qualificacao_representante = EXCLUDED.qualificacao_representante,
    faixa_etaria = EXCLUDED.faixa_etaria,
    updated_at = NOW();
"@
    }

    Run-Psql "DROP TABLE receita.stg_socios;"
    $total = Get-TableCount "socios"
    $sw.Stop()
    $novos = $total - $antes; if ($novos -lt 0) { $novos = 0 }
    Log-Ingestao -Tabela "socios" -Antes $antes -Novos $novos -Total $total -Duracao $sw.Elapsed.TotalSeconds
    Write-Log "  SOCIOS: antes=$antes novos=$novos total=$total ($([math]::Round($sw.Elapsed.TotalSeconds, 1))s)"
}

# ── SIMPLES ──────────────────────────────────────────────────

function Load-Simples {
    $sw = [System.Diagnostics.Stopwatch]::StartNew()
    $antes = 0
    try { $antes = Get-TableCount "simples" } catch {}

    Write-Log "  Criando staging simples..."
    Run-Psql @"
DROP TABLE IF EXISTS receita.stg_simples;
CREATE TABLE receita.stg_simples (
    cnpj_basico VARCHAR(8), opcao_simples VARCHAR(1),
    data_opcao_simples VARCHAR(8), data_exclusao_simples VARCHAR(8),
    opcao_mei VARCHAR(1), data_opcao_mei VARCHAR(8), data_exclusao_mei VARCHAR(8)
);
"@

    $simplesCols = "cnpj_basico, opcao_simples, data_opcao_simples, data_exclusao_simples, opcao_mei, data_opcao_mei, data_exclusao_mei"

    $csvFiles = Get-ChildItem $TempDir -Filter "*SIMPLES*" -Exclude "*.zip" | Sort-Object Name
    foreach ($f in $csvFiles) {
        Write-Log "  COPY $($f.Name)..."
        Run-Copy -Table "receita.stg_simples" -Columns $simplesCols -FilePath $f.FullName
    }

    Write-Log "  Deduplicando staging simples..."
    Run-Psql @"
DELETE FROM receita.stg_simples a USING receita.stg_simples b
WHERE a.ctid < b.ctid AND a.cnpj_basico = b.cnpj_basico;
"@

    if ($antes -eq 0) {
        Write-Log "  INSERT direto simples (tabela vazia)..."
        Run-Psql @"
INSERT INTO receita.simples ($simplesCols)
SELECT $simplesCols FROM receita.stg_simples;
"@
    } else {
        Write-Log "  UPSERT simples (incremental)..."
        Run-Psql @"
INSERT INTO receita.simples ($simplesCols)
SELECT $simplesCols FROM receita.stg_simples
ON CONFLICT (cnpj_basico) DO UPDATE SET
    opcao_simples = EXCLUDED.opcao_simples,
    data_opcao_simples = EXCLUDED.data_opcao_simples,
    data_exclusao_simples = EXCLUDED.data_exclusao_simples,
    opcao_mei = EXCLUDED.opcao_mei,
    data_opcao_mei = EXCLUDED.data_opcao_mei,
    data_exclusao_mei = EXCLUDED.data_exclusao_mei,
    updated_at = NOW();
"@
    }

    Run-Psql "DROP TABLE receita.stg_simples;"
    $total = Get-TableCount "simples"
    $sw.Stop()
    $novos = $total - $antes; if ($novos -lt 0) { $novos = 0 }
    Log-Ingestao -Tabela "simples" -Antes $antes -Novos $novos -Total $total -Duracao $sw.Elapsed.TotalSeconds
    Write-Log "  SIMPLES: antes=$antes novos=$novos total=$total ($([math]::Round($sw.Elapsed.TotalSeconds, 1))s)"
}

# ── EXECUTAR ─────────────────────────────────────────────────

Write-Log "========== CONTINUAÇÃO: Estabelecimentos, Sócios, Simples =========="

Load-Estabelecimentos
Load-Socios
Load-Simples

Write-Log "Atualizando estatísticas..."
Run-Psql "ANALYZE receita.empresas; ANALYZE receita.estabelecimentos; ANALYZE receita.socios; ANALYZE receita.simples;"

Write-Log "=== RESUMO FINAL ==="
$resumo = & $PsqlExe -h $PgHost -p $PgPort -U $PgUser -d $PgDb -c @"
SELECT 'empresas' AS tabela, count(*) AS registros FROM receita.empresas
UNION ALL SELECT 'estabelecimentos', count(*) FROM receita.estabelecimentos
UNION ALL SELECT 'socios', count(*) FROM receita.socios
UNION ALL SELECT 'simples', count(*) FROM receita.simples
ORDER BY tabela;
"@
Write-Host $resumo
Write-Log ($resumo -join "`n")

Write-Log "========== INGESTÃO CONCLUÍDA =========="
