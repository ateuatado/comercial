-- Importa os CSVs no banco DESTINO: spivvps
\timing on

\COPY receita.cnaes         FROM 'C:/Temp/spiv_migra/cnaes.csv'         CSV HEADER
\COPY receita.motivos       FROM 'C:/Temp/spiv_migra/motivos.csv'       CSV HEADER
\COPY receita.municipios    FROM 'C:/Temp/spiv_migra/municipios.csv'    CSV HEADER
\COPY receita.naturezas     FROM 'C:/Temp/spiv_migra/naturezas.csv'     CSV HEADER
\COPY receita.paises        FROM 'C:/Temp/spiv_migra/paises.csv'        CSV HEADER
\COPY receita.qualificacoes FROM 'C:/Temp/spiv_migra/qualificacoes.csv' CSV HEADER
\echo 'Dominios importados.'

\COPY receita.empresas        FROM 'C:/Temp/spiv_migra/empresas.csv'        CSV HEADER
\echo 'Empresas importadas.'

\COPY receita.estabelecimentos FROM 'C:/Temp/spiv_migra/estabelecimentos.csv' CSV HEADER
\echo 'Estabelecimentos importados.'

\COPY receita.socios(cnpj_basico, identificador_socio, nome_socio, cpf_cnpj_socio, qualificacao_socio, data_entrada_sociedade, pais, representante_legal, nome_representante, qualificacao_representante, faixa_etaria, created_at, updated_at) FROM 'C:/Temp/spiv_migra/socios.csv' CSV HEADER
\echo 'Socios importados.'

\COPY receita.simples FROM 'C:/Temp/spiv_migra/simples.csv' CSV HEADER
\echo 'Simples importado.'

-- Indices
CREATE INDEX IF NOT EXISTS idx_empresas_natureza  ON receita.empresas(natureza_juridica);
CREATE INDEX IF NOT EXISTS idx_empresas_porte     ON receita.empresas(porte_empresa);
CREATE INDEX IF NOT EXISTS idx_estab_cnpj_basico  ON receita.estabelecimentos(cnpj_basico);
CREATE INDEX IF NOT EXISTS idx_estab_uf           ON receita.estabelecimentos(uf);
CREATE INDEX IF NOT EXISTS idx_estab_situacao     ON receita.estabelecimentos(situacao_cadastral);
CREATE INDEX IF NOT EXISTS idx_socios_cnpj_basico ON receita.socios(cnpj_basico);
CREATE INDEX IF NOT EXISTS idx_simples_opcao      ON receita.simples(opcao_simples);

-- Resumo final
SELECT 'empresas'         AS tabela, COUNT(*) AS registros FROM receita.empresas
UNION ALL SELECT 'estabelecimentos', COUNT(*) FROM receita.estabelecimentos
UNION ALL SELECT 'socios',           COUNT(*) FROM receita.socios
UNION ALL SELECT 'simples',          COUNT(*) FROM receita.simples
UNION ALL SELECT 'cnaes',            COUNT(*) FROM receita.cnaes
UNION ALL SELECT 'municipios',       COUNT(*) FROM receita.municipios;

\echo '=== Importacao concluida! ==='
