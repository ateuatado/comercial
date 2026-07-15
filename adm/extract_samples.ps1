Add-Type -AssemblyName System.IO.Compression.FileSystem

$zipPath = "c:\xampp\htdocs\spiv\adm\download.zip"
$tempDir = "c:\xampp\htdocs\spiv\adm\temp_extract"

if (-not (Test-Path $tempDir)) {
    New-Item -ItemType Directory -Path $tempDir | Out-Null
}

$zip = [System.IO.Compression.ZipFile]::OpenRead($zipPath)

$smallFiles = @("Cnaes.zip", "Motivos.zip", "Municipios.zip", "Naturezas.zip", "Paises.zip", "Qualificacoes.zip", "Simples.zip")
$sampleFiles = @("Empresas0.zip", "Estabelecimentos0.zip", "Socios0.zip")

foreach ($name in ($smallFiles + $sampleFiles)) {
    $entry = $zip.Entries | Where-Object { $_.FullName -eq $name }
    if ($entry) {
        $dest = Join-Path $tempDir $name
        if (Test-Path $dest) { Remove-Item $dest }
        [System.IO.Compression.ZipFileExtensions]::ExtractToFile($entry, $dest, $true)
        Write-Host "Extracted: $name"
    }
}

$zip.Dispose()

# Now extract inner zips and get first 3 lines of each CSV
$innerFiles = Get-ChildItem $tempDir -Filter "*.zip"
foreach ($f in $innerFiles) {
    $innerZip = [System.IO.Compression.ZipFile]::OpenRead($f.FullName)
    foreach ($e in $innerZip.Entries) {
        $csvDest = Join-Path $tempDir $e.Name
        if (Test-Path $csvDest) { Remove-Item $csvDest }
        [System.IO.Compression.ZipFileExtensions]::ExtractToFile($e, $csvDest, $true)
    }
    $innerZip.Dispose()
}

# Show first 3 lines of each CSV
$csvFiles = Get-ChildItem $tempDir -Filter "*.csv" | Sort-Object Name
foreach ($csv in $csvFiles) {
    Write-Host "`n=== $($csv.Name) ==="
    Get-Content $csv.FullName -TotalCount 3 -Encoding UTF8
}
