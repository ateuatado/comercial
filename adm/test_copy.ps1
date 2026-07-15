$env:PGPASSWORD = "LulaTetra26"
$psql = "C:\Program Files\PostgreSQL\18\bin\psql.exe"
$TempDir = "c:\xampp\htdocs\spiv\adm\temp_extract"

# First drop and recreate to test clean
& $psql -U postgres -d spiv -c "TRUNCATE TABLE receita.cnaes;" 2>&1

# Write the \copy command to a temp file
$tmpSql = Join-Path $TempDir "_test_copy.sql"
$csvFile = Get-ChildItem $TempDir -Filter "*CNAECSV" | Select-Object -First 1

$copyLine = "\copy receita.cnaes (codigo, descricao) FROM '$($csvFile.FullName)' WITH (FORMAT csv, DELIMITER ';', QUOTE '""', ENCODING 'LATIN1')"
Write-Host "Command: $copyLine"
Set-Content -Path $tmpSql -Value $copyLine -Encoding UTF8 -NoNewline

Write-Host "File content:"
Get-Content $tmpSql

Write-Host ""
Write-Host "Executing..."
& $psql -U postgres -d spiv -f $tmpSql -v ON_ERROR_STOP=1 2>&1

Write-Host ""
Write-Host "Count:"
& $psql -U postgres -d spiv -t -A -c "SELECT count(*) FROM receita.cnaes;" 2>$null
