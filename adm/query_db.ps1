$env:PGPASSWORD = "LulaTetra26"
$psql = "C:\Program Files\PostgreSQL\18\bin\psql.exe"

Write-Host "=== SCHEMAS ==="
& $psql -U postgres -d spiv -c "\dn"

Write-Host "=== TABLES ==="
& $psql -U postgres -d spiv -c "\dt *.*"

Write-Host "=== VIEWS ==="
& $psql -U postgres -d spiv -c "\dv *.*"
