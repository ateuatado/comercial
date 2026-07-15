$env:PGPASSWORD = "LulaTetra26"
$psql = "C:\Program Files\PostgreSQL\18\bin\psql.exe"
$tempDir = "C:\xampp\htdocs\spiv\adm\temp_extract"

$files = Get-ChildItem -Path $tempDir -Filter "*ESTABELE" | Sort-Object Name

Write-Host "Iniciando importação de $($files.Count) arquivos para receita.estabelecimentos..."

# Truncate table first to guarantee a clean state
Write-Host "Limpando tabela receita.estabelecimentos..."
& $psql -U postgres -d spiv -c "TRUNCATE TABLE receita.estabelecimentos;"

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

Write-Host "--------------------------------------------------"
Write-Host "Calculando contagem total..."
& $psql -U postgres -d spiv -c "SELECT COUNT(*) FROM receita.estabelecimentos;"

Write-Host "Ingestão concluída com sucesso!"
