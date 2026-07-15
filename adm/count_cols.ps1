$dir = "c:\xampp\htdocs\spiv\adm\temp_extract"

# Count columns for Empresas
$empFile = "K3241.K03200Y0.D60613.EMPRECSV"
$empPath = Join-Path $dir $empFile
Write-Host "=== EMPRESAS ==="
$line = Get-Content $empPath -TotalCount 1 -Encoding Default
$cols = $line.Split(';')
Write-Host "Colunas: $($cols.Count)"
for ($i = 0; $i -lt $cols.Count; $i++) {
    Write-Host "  Col $i`: $($cols[$i])"
}

# Count columns for Estabelecimentos
$estFile = "K3241.K03200Y0.D60613.ESTABELE"
$estPath = Join-Path $dir $estFile
Write-Host ""
Write-Host "=== ESTABELECIMENTOS ==="
$line = Get-Content $estPath -TotalCount 1 -Encoding Default
$cols = $line.Split(';')
Write-Host "Colunas: $($cols.Count)"
for ($i = 0; $i -lt $cols.Count; $i++) {
    Write-Host "  Col $i`: $($cols[$i])"
}

# Try to read more empresas lines to understand patterns
Write-Host ""
Write-Host "=== EMPRESAS (5 lines) ==="
Get-Content $empPath -TotalCount 5 -Encoding Default
