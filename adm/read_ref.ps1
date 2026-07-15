$dir = "c:\xampp\htdocs\spiv\adm\temp_extract"

$files = @(
    "F.K03200`$Z.D60613.CNAECSV",
    "F.K03200`$Z.D60613.MOTICSV",
    "F.K03200`$Z.D60613.MUNICCSV",
    "F.K03200`$Z.D60613.NATJUCSV",
    "F.K03200`$Z.D60613.PAISCSV",
    "F.K03200`$Z.D60613.QUALSCSV"
)

foreach ($f in $files) {
    $path = Join-Path $dir $f
    if (Test-Path $path) {
        Write-Host ""
        Write-Host "=== $f ==="
        Get-Content $path -TotalCount 5 -Encoding Default
    } else {
        Write-Host "NOT FOUND: $f"
    }
}
