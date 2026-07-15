$dir = "c:\xampp\htdocs\spiv\adm\temp_extract"
$csvFiles = Get-ChildItem $dir -Filter "*.CSV" | Sort-Object Name

foreach ($csv in $csvFiles) {
    Write-Host ""
    Write-Host "=== $($csv.Name) ==="
    Get-Content $csv.FullName -TotalCount 3 -Encoding Default
}

# Also check the SIMPLES file
$simplesFile = Get-ChildItem $dir -Filter "*SIMPLES*"
if ($simplesFile) {
    Write-Host ""
    Write-Host "=== $($simplesFile.Name) ==="
    Get-Content $simplesFile.FullName -TotalCount 3 -Encoding Default
}

# Check Empresas
$empFile = Get-ChildItem $dir -Filter "*EMPRE*"
if ($empFile) {
    Write-Host ""
    Write-Host "=== $($empFile.Name) ==="
    Get-Content $empFile.FullName -TotalCount 3 -Encoding Default
}

# Check Estabelecimentos
$estFile = Get-ChildItem $dir -Filter "*ESTABELE*"
if ($estFile) {
    Write-Host ""
    Write-Host "=== $($estFile.Name) ==="
    Get-Content $estFile.FullName -TotalCount 3 -Encoding Default
}
