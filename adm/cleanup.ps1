$env:PGPASSWORD = "LulaTetra26"
$psql = "C:\Program Files\PostgreSQL\18\bin\psql.exe"

Write-Host "=== Limpando staging e dados parciais ==="
& $psql -U postgres -d spiv -c "DROP TABLE IF EXISTS receita.stg_estabelecimentos;"
& $psql -U postgres -d spiv -c "DROP TABLE IF EXISTS receita.stg_socios;"
& $psql -U postgres -d spiv -c "DROP TABLE IF EXISTS receita.stg_simples;"
& $psql -U postgres -d spiv -c "TRUNCATE TABLE receita.estabelecimentos;"

Write-Host "=== Estado atual ==="
& $psql -U postgres -d spiv -c "SELECT 'empresas' AS tabela, count(*) FROM receita.empresas UNION ALL SELECT 'estab', count(*) FROM receita.estabelecimentos UNION ALL SELECT 'socios', count(*) FROM receita.socios UNION ALL SELECT 'simples', count(*) FROM receita.simples;"
