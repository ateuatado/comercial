$env:PGPASSWORD = "LulaTetra26"
$psql = "C:\Program Files\PostgreSQL\18\bin\psql.exe"
$tempDir = "C:\xampp\htdocs\spiv\adm\temp_extract"

$files = Get-ChildItem -Path $tempDir -Filter "*ESTABELE" | Sort-Object Name

Write-Host "=== INGESTÃO ULTRA RÁPIDA DE ESTABELECIMENTOS ==="
$overallStart = Get-Date

# 1. Dropar todos os índices e constraints para acelerar a carga de dados
Write-Host "Dropando índices e restrições existentes para acelerar a carga de dados..."
$dropSql = @"
ALTER TABLE receita.estabelecimentos DROP CONSTRAINT IF EXISTS pk_estabelecimentos CASCADE;
DROP INDEX IF EXISTS receita.idx_estab_cnpj_basico;
DROP INDEX IF EXISTS receita.idx_estab_uf;
DROP INDEX IF EXISTS receita.idx_estab_municipio;
DROP INDEX IF EXISTS receita.idx_estab_cnae;
DROP INDEX IF EXISTS receita.idx_estab_situacao;
DROP INDEX IF EXISTS receita.idx_estab_cep;
DROP INDEX IF EXISTS receita.idx_estab_dt_inicio;
DROP INDEX IF EXISTS receita.idx_estab_nome_fantasia;
TRUNCATE TABLE receita.estabelecimentos;
"@
& $psql -U postgres -d spiv -c $dropSql

# 2. Ingerir arquivos em lote sem índices ativos
foreach ($file in $files) {
    Write-Host "--------------------------------------------------"
    Write-Host "Importando: $($file.Name) ($([Math]::Round($file.Length / 1GB, 2)) GB)..."
    $startTime = Get-Date
    
    $sqlCmd = "\copy receita.estabelecimentos (cnpj_basico, cnpj_ordem, cnpj_dv, identificador_matriz_filial, nome_fantasia, situacao_cadastral, data_situacao_cadastral, motivo_situacao_cadastral, nome_cidade_exterior, pais, data_inicio_atividade, cnae_fiscal_principal, cnae_fiscal_secundaria, tipo_logradouro, logradouro, numero, complemento, bairro, cep, uf, municipio, ddd_1, telefone_1, ddd_2, telefone_2, ddd_fax, fax, email, situacao_especial, data_situacao_especial) FROM '$($file.FullName.Replace('\','\\'))' WITH (FORMAT csv, DELIMITER ';', QUOTE '""', ENCODING 'LATIN1')"
    
    $tmpSql = Join-Path $tempDir "_copy_temp.sql"
    Set-Content -Path $tmpSql -Value $sqlCmd -Encoding UTF8 -NoNewline
    
    & $psql -U postgres -d spiv -f $tmpSql -v ON_ERROR_STOP=1
    
    $endTime = Get-Date
    $duration = ($endTime - $startTime).TotalSeconds
    Write-Host "Concluído $($file.Name) em $([Math]::Round($duration, 2)) segundos."
}

if (Test-Path (Join-Path $tempDir "_copy_temp.sql")) {
    Remove-Item -Path (Join-Path $tempDir "_copy_temp.sql") -Force
}

# 3. Recriar os índices em lote (com alocação extra de memória para ordenação rápida)
Write-Host "--------------------------------------------------"
Write-Host "Recriando os índices em lote (isso pode levar alguns minutos, mas é muito mais rápido que atualizar linha por linha)..."

$recreateSql = @"
SET maintenance_work_mem = '2GB';

Write-Host "Criando pk_estabelecimentos...";
ALTER TABLE receita.estabelecimentos ADD CONSTRAINT pk_estabelecimentos PRIMARY KEY (cnpj_basico, cnpj_ordem, cnpj_dv);

Write-Host "Criando idx_estab_cnpj_basico...";
CREATE INDEX idx_estab_cnpj_basico ON receita.estabelecimentos USING btree (cnpj_basico);

Write-Host "Criando idx_estab_uf...";
CREATE INDEX idx_estab_uf ON receita.estabelecimentos USING btree (uf);

Write-Host "Criando idx_estab_municipio...";
CREATE INDEX idx_estab_municipio ON receita.estabelecimentos USING btree (municipio);

Write-Host "Criando idx_estab_cnae...";
CREATE INDEX idx_estab_cnae ON receita.estabelecimentos USING btree (cnae_fiscal_principal);

Write-Host "Criando idx_estab_situacao...";
CREATE INDEX idx_estab_situacao ON receita.estabelecimentos USING btree (situacao_cadastral);

Write-Host "Criando idx_estab_cep...";
CREATE INDEX idx_estab_cep ON receita.estabelecimentos USING btree (cep);

Write-Host "Criando idx_estab_dt_inicio...";
CREATE INDEX idx_estab_dt_inicio ON receita.estabelecimentos USING btree (data_inicio_atividade);

Write-Host "Criando GIN index idx_estab_nome_fantasia (isso pode demorar devido ao cálculo de trigramas)...";
CREATE INDEX idx_estab_nome_fantasia ON receita.estabelecimentos USING gin (nome_fantasia gin_trgm_ops);
"@

$tmpIndexSql = Join-Path $tempDir "_index_temp.sql"
$cleanSql = $recreateSql -replace "Write-Host .*;",""
Set-Content -Path $tmpIndexSql -Value $cleanSql -Encoding UTF8 -NoNewline

Write-Host "Executando script de recriação de índices..."
& $psql -U postgres -d spiv -f $tmpIndexSql -v ON_ERROR_STOP=1

if (Test-Path $tmpIndexSql) {
    Remove-Item -Path $tmpIndexSql -Force
}

$overallEnd = Get-Date
$overallDuration = ($overallEnd - $overallStart).TotalMinutes
Write-Host "--------------------------------------------------"
Write-Host "Carga e indexação concluídas com sucesso!"
Write-Host "Tempo total de execução: $([Math]::Round($overallDuration, 2)) minutos."

& $psql -U postgres -d spiv -c "SELECT COUNT(*) FROM receita.estabelecimentos;"
