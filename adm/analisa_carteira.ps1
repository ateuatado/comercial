$csv = Import-Csv -Path 'C:\xampp\htdocs\spiv\ingestao\relarorio_geral_carteiras_clientes.csv' -Delimiter ';' -Encoding UTF8
Write-Host "Total de linhas: $($csv.Count)"
Write-Host ""
Write-Host "=== CICLO_DE_VIDA unicos ==="
$csv | Group-Object CICLO_DE_VIDA | Select-Object Name, Count | Format-Table
Write-Host "=== PROSPECCAO unicos ==="
$csv | Group-Object PROSPECCAO | Select-Object Name, Count | Format-Table
Write-Host "=== CATEGORIA_INSTITUCIONAL unicos ==="
$csv | Group-Object CATEGORIA_INSTITUCIONAL | Select-Object Name, Count | Format-Table
Write-Host "=== Matriculas unicas ==="
$uniqMat = ($csv | Select-Object -ExpandProperty MATRICULA_MCMCU -Unique)
Write-Host $uniqMat.Count
Write-Host "=== CNPJs unicos ==="
$uniqCnpj = ($csv | Select-Object -ExpandProperty CNPJ -Unique)
Write-Host $uniqCnpj.Count
Write-Host "=== SE unicos ==="
$csv | Group-Object SE | Select-Object Name, Count | Format-Table
Write-Host "=== CONTA_NUMERO - linhas com conta preenchida ==="
($csv | Where-Object { $_.CONTA_NUMERO -ne '' -and $_.CONTA_NUMERO -ne $null } | Measure-Object).Count
Write-Host "=== 3 primeiras linhas - amostra completa ==="
$csv | Select-Object -First 3 | Format-List
