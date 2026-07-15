$env:PGPASSWORD = "LulaTetra26"
$psql = "C:\Program Files\PostgreSQL\18\bin\psql.exe"

Write-Host "=== SCHEMAS ==="
& $psql -U postgres -d spiv -c "\dn"

Write-Host "=== PUBLIC TABLES ==="
& $psql -U postgres -d spiv -c "\dt public.*"

Write-Host "=== PUBLIC VIEWS ==="
& $psql -U postgres -d spiv -c "\dv public.*"

Write-Host "=== ALL USER SCHEMAS TABLES ==="
& $psql -U postgres -d spiv -c "SELECT schemaname, tablename FROM pg_tables WHERE schemaname NOT IN ('pg_catalog', 'information_schema') ORDER BY schemaname, tablename;"

Write-Host "=== ALL USER VIEWS ==="
& $psql -U postgres -d spiv -c "SELECT schemaname, viewname FROM pg_views WHERE schemaname NOT IN ('pg_catalog', 'information_schema') ORDER BY schemaname, viewname;"
