$env:PGPASSWORD = "LulaTetra26"
$psql = "C:\Program Files\PostgreSQL\18\bin\psql.exe"

Write-Host "=== Queries ativas ==="
& $psql -U postgres -d spiv -c "SELECT pid, state, wait_event_type, query_start, LEFT(query, 100) as query FROM pg_stat_activity WHERE datname = 'spiv' AND state = 'active' AND pid <> pg_backend_pid();"

Write-Host ""
Write-Host "=== Contagem atual empresas ==="
& $psql -U postgres -d spiv -t -A -c "SELECT count(*) FROM receita.empresas;" 2>$null

Write-Host ""
Write-Host "=== Staging empresas existe? ==="
& $psql -U postgres -d spiv -t -A -c "SELECT EXISTS(SELECT 1 FROM pg_tables WHERE schemaname='receita' AND tablename='stg_empresas');" 2>$null
