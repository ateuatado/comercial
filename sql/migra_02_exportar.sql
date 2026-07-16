-- Exporta amostra de 100k empresas + relacionados (roda no banco ORIGEM: spiv)
-- Salva em C:\Temp\spiv_migra\

\timing on

-- Tabela temporária com amostra
CREATE TEMP TABLE _amostra AS
SELECT cnpj_basico FROM receita.empresas TABLESAMPLE SYSTEM(2) LIMIT 100000;
CREATE INDEX ON _amostra(cnpj_basico);

\echo 'Amostra selecionada. Exportando...'

\COPY (SELECT * FROM receita.cnaes)         TO 'C:/Temp/spiv_migra/cnaes.csv'         CSV HEADER
\COPY (SELECT * FROM receita.motivos)       TO 'C:/Temp/spiv_migra/motivos.csv'       CSV HEADER
\COPY (SELECT * FROM receita.municipios)    TO 'C:/Temp/spiv_migra/municipios.csv'    CSV HEADER
\COPY (SELECT * FROM receita.naturezas)     TO 'C:/Temp/spiv_migra/naturezas.csv'     CSV HEADER
\COPY (SELECT * FROM receita.paises)        TO 'C:/Temp/spiv_migra/paises.csv'        CSV HEADER
\COPY (SELECT * FROM receita.qualificacoes) TO 'C:/Temp/spiv_migra/qualificacoes.csv' CSV HEADER

\echo 'Dominios exportados. Exportando empresas...'

\COPY (SELECT e.* FROM receita.empresas e JOIN _amostra a ON a.cnpj_basico = e.cnpj_basico) TO 'C:/Temp/spiv_migra/empresas.csv' CSV HEADER

\echo 'Exportando estabelecimentos...'

\COPY (SELECT est.* FROM receita.estabelecimentos est JOIN _amostra a ON a.cnpj_basico = est.cnpj_basico) TO 'C:/Temp/spiv_migra/estabelecimentos.csv' CSV HEADER

\echo 'Exportando socios...'

\COPY (SELECT sc.* FROM receita.socios sc JOIN _amostra a ON a.cnpj_basico = sc.cnpj_basico) TO 'C:/Temp/spiv_migra/socios.csv' CSV HEADER

\echo 'Exportando simples...'

\COPY (SELECT s.* FROM receita.simples s JOIN _amostra a ON a.cnpj_basico = s.cnpj_basico) TO 'C:/Temp/spiv_migra/simples.csv' CSV HEADER

\echo '=== Exportacao concluida! ==='
