$env:PGPASSWORD = "LulaTetra26"
$psql = "C:\Program Files\PostgreSQL\18\bin\psql.exe"

Write-Host "=== Criando extensao pg_trgm ==="
& $psql -U postgres -d spiv -c "CREATE EXTENSION IF NOT EXISTS pg_trgm;" 2>&1

Write-Host "=== Executando 01_criar_schema.sql ==="
& $psql -U postgres -d spiv -f "c:\xampp\htdocs\spiv\sql\01_criar_schema.sql" -v ON_ERROR_STOP=1 2>&1

Write-Host ""
Write-Host "=== Verificando tabelas criadas ==="
& $psql -U postgres -d spiv -c "SELECT schemaname, tablename FROM pg_tables WHERE schemaname = 'receita' ORDER BY tablename;"

Write-Host ""
Write-Host "=== Verificando views criadas ==="
& $psql -U postgres -d spiv -c "SELECT schemaname, viewname FROM pg_views WHERE schemaname = 'receita' ORDER BY viewname;"

Write-Host ""
Write-Host "=== Verificando indices ==="
& $psql -U postgres -d spiv -c "SELECT indexname FROM pg_indexes WHERE schemaname = 'receita' ORDER BY indexname;"
