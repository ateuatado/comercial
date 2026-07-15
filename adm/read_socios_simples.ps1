$dir = "c:\xampp\htdocs\spiv\adm\temp_extract"

# List all files now
Write-Host "=== ALL FILES ==="
Get-ChildItem $dir | Select-Object Name, Length | Format-Table -AutoSize

# Find Socios file
$socFiles = Get-ChildItem $dir -Filter "*SOCIO*"
foreach ($f in $socFiles) {
    Write-Host ""
    Write-Host "=== $($f.Name) ==="
    $line = Get-Content $f.FullName -TotalCount 1 -Encoding Default
    $cols = $line.Split(';')
    Write-Host "Colunas: $($cols.Count)"
    for ($i = 0; $i -lt $cols.Count; $i++) {
        Write-Host "  Col $i`: $($cols[$i])"
    }
    Write-Host ""
    Get-Content $f.FullName -TotalCount 3 -Encoding Default
}

# Find Simples file
$simpFiles = Get-ChildItem $dir -Filter "*SIMPLES*"
foreach ($f in $simpFiles) {
    Write-Host ""
    Write-Host "=== $($f.Name) ==="
    $line = Get-Content $f.FullName -TotalCount 1 -Encoding Default
    $cols = $line.Split(';')
    Write-Host "Colunas: $($cols.Count)"
    for ($i = 0; $i -lt $cols.Count; $i++) {
        Write-Host "  Col $i`: $($cols[$i])"
    }
    Write-Host ""
    Get-Content $f.FullName -TotalCount 3 -Encoding Default
}
