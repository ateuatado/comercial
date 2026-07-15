"""
SPIV — Ingestão de Dados da Receita Federal (CNPJ)
===================================================
Uso:   python ingestao_receita.py [--skip-extract] [--skip-tables empresas,socios]
Local: c:\\xampp\\htdocs\\spiv\\ingestao\\
Reqs:  Python 3.10+, psycopg2, PostgreSQL 18
"""

import os
import sys
import time
import logging
import argparse
import zipfile
from pathlib import Path
from datetime import datetime

import psycopg2
from psycopg2 import sql

# ── Configuração ─────────────────────────────────────────────

CONFIG = {
    "zip_path": r"c:\xampp\htdocs\spiv\adm\download.zip",
    "temp_dir": r"c:\xampp\htdocs\spiv\adm\temp_extract",
    "sql_dir":  r"c:\xampp\htdocs\spiv\sql",
    "db": {
        "host": "localhost",
        "port": 5432,
        "dbname": "spiv",
        "user": "postgres",
        "password": "LulaTetra26",
    },
}

# ── Logging ──────────────────────────────────────────────────

LOG_FILE = Path(__file__).parent / "ingestao.log"

logging.basicConfig(
    level=logging.INFO,
    format="[%(asctime)s] [%(levelname)s] %(message)s",
    datefmt="%Y-%m-%d %H:%M:%S",
    handlers=[
        logging.StreamHandler(sys.stdout),
        logging.FileHandler(LOG_FILE, encoding="utf-8"),
    ],
)
log = logging.getLogger("ingestao")

# ── Helpers ──────────────────────────────────────────────────

def get_conn():
    """Retorna conexão PostgreSQL."""
    return psycopg2.connect(**CONFIG["db"])

def execute(conn, query, commit=True):
    """Executa SQL e opcionalmente faz commit."""
    with conn.cursor() as cur:
        cur.execute(query)
        if commit:
            conn.commit()

def get_count(conn, table):
    """Retorna count(*) de uma tabela no schema receita."""
    with conn.cursor() as cur:
        cur.execute(f"SELECT count(*) FROM receita.{table}")
        return cur.fetchone()[0]

def copy_csv(conn, table, columns, filepath):
    """Faz COPY de um CSV para a tabela usando copy_expert.
    Usa Python csv module para lidar com campos multilinha e aspas malformadas.
    Grava arquivo limpo em disco e envia ao PostgreSQL.
    """
    import csv
    import tempfile

    copy_sql = f"""COPY {table} ({columns}) FROM STDIN WITH (
        FORMAT csv, DELIMITER ';', QUOTE '"', ENCODING 'UTF8'
    )"""

    num_cols = len(columns.split(","))
    bad_lines = 0
    good_lines = 0

    tmp = tempfile.NamedTemporaryFile(mode="w", suffix=".csv", encoding="utf-8",
                                       delete=False, dir=CONFIG["temp_dir"],
                                       newline="")
    try:
        writer = csv.writer(tmp, delimiter=";", quotechar='"', quoting=csv.QUOTE_MINIMAL)

        with open(filepath, "r", encoding="latin1", errors="replace") as f:
            reader = csv.reader(f, delimiter=";", quotechar='"')
            for row in reader:
                if len(row) == num_cols:
                    # Remove bytes NULL (0x00) que PostgreSQL rejeita
                    cleaned = [col.replace('\x00', '') for col in row]
                    writer.writerow(cleaned)
                    good_lines += 1
                else:
                    bad_lines += 1

        tmp.close()

        if bad_lines > 0:
            log.warning(f"  {bad_lines:,} linhas descartadas (colunas != {num_cols})")
        log.info(f"  {good_lines:,} linhas válidas para COPY")

        with open(tmp.name, "rb") as clean:
            with conn.cursor() as cur:
                cur.copy_expert(copy_sql, clean)
        conn.commit()
    finally:
        try:
            os.unlink(tmp.name)
        except OSError:
            pass

def log_ingestao(conn, tabela, antes, novos, total, duracao):
    """Registra no log_ingestao."""
    execute(conn, f"""
        INSERT INTO receita.log_ingestao (tabela, registros_antes, registros_novos, registros_total, duracao_seg, status)
        VALUES ('{tabela}', {antes}, {novos}, {total}, {duracao:.1f}, 'concluido')
    """)

def find_files(pattern):
    """Busca arquivos no temp_dir pelo padrão."""
    temp = Path(CONFIG["temp_dir"])
    files = sorted(temp.glob(pattern))
    log.info(f"  Encontrados {len(files)} arquivos ({pattern})")
    return files

def format_size(size_bytes):
    """Formata tamanho em MB ou GB."""
    if size_bytes > 1_073_741_824:
        return f"{size_bytes / 1_073_741_824:.1f}GB"
    return f"{size_bytes / 1_048_576:.0f}MB"

# ── ETAPA 1: Extração ───────────────────────────────────────

def extract_zips():
    """Extrai download.zip e todos os ZIPs internos."""
    zip_path = Path(CONFIG["zip_path"])
    temp_dir = Path(CONFIG["temp_dir"])

    if not zip_path.exists():
        log.error(f"Arquivo {zip_path} não encontrado!")
        sys.exit(1)

    temp_dir.mkdir(parents=True, exist_ok=True)

    log.info(f"Extraindo ZIP principal ({format_size(zip_path.stat().st_size)})...")
    with zipfile.ZipFile(zip_path, "r") as zf:
        zf.extractall(temp_dir)
    log.info("ZIP principal extraído.")

    log.info("Extraindo ZIPs internos...")
    for inner_zip in sorted(temp_dir.glob("*.zip")):
        log.info(f"  Extraindo {inner_zip.name}...")
        try:
            with zipfile.ZipFile(inner_zip, "r") as zf:
                zf.extractall(temp_dir)
        except Exception as e:
            log.warning(f"  Falha ao extrair {inner_zip.name}: {e}")
    log.info("ZIPs internos extraídos.")

# ── ETAPA 2: Schema ─────────────────────────────────────────

def create_schema(conn):
    """Cria schema e extensões (idempotente)."""
    log.info("Verificando/criando schema...")
    execute(conn, "CREATE EXTENSION IF NOT EXISTS pg_trgm;")

    sql_file = Path(CONFIG["sql_dir"]) / "01_criar_schema.sql"
    with open(sql_file, "r", encoding="utf-8") as f:
        execute(conn, f.read())
    log.info("Schema verificado/criado.")

# ── ETAPA 3: Tabelas de Domínio ─────────────────────────────

DOMINIO_MAP = {
    "cnaes":         "*CNAECSV",
    "motivos":       "*MOTICSV",
    "municipios":    "*MUNICCSV",
    "naturezas":     "*NATJUCSV",
    "paises":        "*PAISCSV",
    "qualificacoes": "*QUALSCSV",
}

def load_dominios(conn):
    """Carrega tabelas de domínio (TRUNCATE + COPY)."""
    log.info("=== Carregando tabelas de domínio ===")

    for tabela, pattern in DOMINIO_MAP.items():
        files = find_files(pattern)
        if not files:
            log.warning(f"  Arquivo para {tabela} não encontrado ({pattern})")
            continue

        csv_file = files[0]
        t0 = time.time()
        log.info(f"  Carregando {tabela} de {csv_file.name}...")

        antes = get_count(conn, tabela)
        execute(conn, f"TRUNCATE TABLE receita.{tabela};")
        copy_csv(conn, f"receita.{tabela}", "codigo, descricao", csv_file)

        total = get_count(conn, tabela)
        duracao = time.time() - t0
        novos = total - antes if total > antes else total

        log_ingestao(conn, tabela, antes, novos, total, duracao)
        log.info(f"  {tabela}: {total:,} registros ({duracao:.1f}s)")

# ── ETAPA 4: Tabelas de Dados ───────────────────────────────

# Definições de cada tabela de dados
TABELAS_DADOS = {
    "empresas": {
        "pattern": "*EMPRECSV",
        "pk": "cnpj_basico",
        "columns": "cnpj_basico, razao_social, natureza_juridica, qualificacao_responsavel, capital_social, porte_empresa, ente_federativo",
        "staging_ddl": """
            CREATE TABLE receita.stg_empresas (
                cnpj_basico VARCHAR(8), razao_social VARCHAR(200),
                natureza_juridica VARCHAR(4), qualificacao_responsavel VARCHAR(2),
                capital_social VARCHAR(20), porte_empresa VARCHAR(2),
                ente_federativo VARCHAR(100)
            )
        """,
        "upsert_set": """
            razao_social = EXCLUDED.razao_social,
            natureza_juridica = EXCLUDED.natureza_juridica,
            qualificacao_responsavel = EXCLUDED.qualificacao_responsavel,
            capital_social = EXCLUDED.capital_social,
            porte_empresa = EXCLUDED.porte_empresa,
            ente_federativo = EXCLUDED.ente_federativo,
            updated_at = NOW()
        """,
        "gin_indexes": [],
    },
    "estabelecimentos": {
        "pattern": "*.ESTABELE",
        "pk": "cnpj_basico, cnpj_ordem, cnpj_dv",
        "columns": "cnpj_basico, cnpj_ordem, cnpj_dv, identificador_matriz_filial, nome_fantasia, situacao_cadastral, data_situacao_cadastral, motivo_situacao_cadastral, nome_cidade_exterior, pais, data_inicio_atividade, cnae_fiscal_principal, cnae_fiscal_secundaria, tipo_logradouro, logradouro, numero, complemento, bairro, cep, uf, municipio, ddd_1, telefone_1, ddd_2, telefone_2, ddd_fax, fax, email, situacao_especial, data_situacao_especial",
        "staging_ddl": """
            CREATE TABLE receita.stg_estabelecimentos (
                cnpj_basico VARCHAR(8), cnpj_ordem VARCHAR(4), cnpj_dv VARCHAR(2),
                identificador_matriz_filial VARCHAR(1), nome_fantasia VARCHAR(200),
                situacao_cadastral VARCHAR(2), data_situacao_cadastral VARCHAR(8),
                motivo_situacao_cadastral VARCHAR(2), nome_cidade_exterior VARCHAR(100),
                pais VARCHAR(3), data_inicio_atividade VARCHAR(8),
                cnae_fiscal_principal VARCHAR(7), cnae_fiscal_secundaria TEXT,
                tipo_logradouro VARCHAR(20), logradouro VARCHAR(200), numero VARCHAR(10),
                complemento VARCHAR(200), bairro VARCHAR(100), cep VARCHAR(8),
                uf VARCHAR(2), municipio VARCHAR(4),
                ddd_1 VARCHAR(4), telefone_1 VARCHAR(10), ddd_2 VARCHAR(4), telefone_2 VARCHAR(10),
                ddd_fax VARCHAR(4), fax VARCHAR(10), email VARCHAR(200),
                situacao_especial VARCHAR(100), data_situacao_especial VARCHAR(8)
            )
        """,
        "upsert_set": """
            identificador_matriz_filial = EXCLUDED.identificador_matriz_filial,
            nome_fantasia = EXCLUDED.nome_fantasia,
            situacao_cadastral = EXCLUDED.situacao_cadastral,
            data_situacao_cadastral = EXCLUDED.data_situacao_cadastral,
            motivo_situacao_cadastral = EXCLUDED.motivo_situacao_cadastral,
            nome_cidade_exterior = EXCLUDED.nome_cidade_exterior,
            pais = EXCLUDED.pais,
            data_inicio_atividade = EXCLUDED.data_inicio_atividade,
            cnae_fiscal_principal = EXCLUDED.cnae_fiscal_principal,
            cnae_fiscal_secundaria = EXCLUDED.cnae_fiscal_secundaria,
            tipo_logradouro = EXCLUDED.tipo_logradouro,
            logradouro = EXCLUDED.logradouro,
            numero = EXCLUDED.numero,
            complemento = EXCLUDED.complemento,
            bairro = EXCLUDED.bairro,
            cep = EXCLUDED.cep, uf = EXCLUDED.uf, municipio = EXCLUDED.municipio,
            ddd_1 = EXCLUDED.ddd_1, telefone_1 = EXCLUDED.telefone_1,
            ddd_2 = EXCLUDED.ddd_2, telefone_2 = EXCLUDED.telefone_2,
            ddd_fax = EXCLUDED.ddd_fax, fax = EXCLUDED.fax,
            email = EXCLUDED.email,
            situacao_especial = EXCLUDED.situacao_especial,
            data_situacao_especial = EXCLUDED.data_situacao_especial,
            updated_at = NOW()
        """,
        "gin_indexes": [
            "CREATE INDEX idx_estab_nome_fantasia ON receita.estabelecimentos USING gin (nome_fantasia gin_trgm_ops)",
        ],
    },
    "socios": {
        "pattern": "*.SOCIOCSV",
        "pk": "cnpj_basico, cpf_cnpj_socio, qualificacao_socio",
        "columns": "cnpj_basico, identificador_socio, nome_socio, cpf_cnpj_socio, qualificacao_socio, data_entrada_sociedade, pais, representante_legal, nome_representante, qualificacao_representante, faixa_etaria",
        "staging_ddl": """
            CREATE TABLE receita.stg_socios (
                cnpj_basico VARCHAR(8), identificador_socio VARCHAR(1),
                nome_socio VARCHAR(200), cpf_cnpj_socio VARCHAR(14),
                qualificacao_socio VARCHAR(2), data_entrada_sociedade VARCHAR(8),
                pais VARCHAR(3), representante_legal VARCHAR(14),
                nome_representante VARCHAR(200), qualificacao_representante VARCHAR(2),
                faixa_etaria VARCHAR(1)
            )
        """,
        "upsert_set": """
            identificador_socio = EXCLUDED.identificador_socio,
            nome_socio = EXCLUDED.nome_socio,
            data_entrada_sociedade = EXCLUDED.data_entrada_sociedade,
            pais = EXCLUDED.pais,
            representante_legal = EXCLUDED.representante_legal,
            nome_representante = EXCLUDED.nome_representante,
            qualificacao_representante = EXCLUDED.qualificacao_representante,
            faixa_etaria = EXCLUDED.faixa_etaria,
            updated_at = NOW()
        """,
        "gin_indexes": [
            "CREATE INDEX idx_socios_nome ON receita.socios USING gin (nome_socio gin_trgm_ops)",
        ],
    },
    "simples": {
        "pattern": "*SIMPLES*CSV*",
        "pk": "cnpj_basico",
        "columns": "cnpj_basico, opcao_simples, data_opcao_simples, data_exclusao_simples, opcao_mei, data_opcao_mei, data_exclusao_mei",
        "staging_ddl": """
            CREATE TABLE receita.stg_simples (
                cnpj_basico VARCHAR(8), opcao_simples VARCHAR(1),
                data_opcao_simples VARCHAR(8), data_exclusao_simples VARCHAR(8),
                opcao_mei VARCHAR(1), data_opcao_mei VARCHAR(8), data_exclusao_mei VARCHAR(8)
            )
        """,
        "upsert_set": """
            opcao_simples = EXCLUDED.opcao_simples,
            data_opcao_simples = EXCLUDED.data_opcao_simples,
            data_exclusao_simples = EXCLUDED.data_exclusao_simples,
            opcao_mei = EXCLUDED.opcao_mei,
            data_opcao_mei = EXCLUDED.data_opcao_mei,
            data_exclusao_mei = EXCLUDED.data_exclusao_mei,
            updated_at = NOW()
        """,
        "gin_indexes": [],
    },
}


def load_tabela_dados(conn, nome, config):
    """Carrega uma tabela de dados usando staging + dedup + insert/upsert."""
    t0 = time.time()
    stg_table = f"receita.stg_{nome}"
    target_table = f"receita.{nome}"
    cols = config["columns"]
    pk = config["pk"]

    antes = get_count(conn, nome)
    log.info(f"=== {nome.upper()} (antes={antes:,}) ===")

    # 1. Dropar índices GIN para acelerar INSERT
    for idx_sql in config["gin_indexes"]:
        idx_name = idx_sql.split("INDEX ")[1].split(" ON")[0]
        log.info(f"  Dropando índice {idx_name}...")
        execute(conn, f"DROP INDEX IF EXISTS receita.{idx_name};")

    # 2. Criar staging
    log.info("  Criando staging...")
    execute(conn, f"DROP TABLE IF EXISTS {stg_table};")
    execute(conn, config["staging_ddl"])

    # 3. COPY dos CSVs para staging
    csv_files = find_files(config["pattern"])
    for f in csv_files:
        size = format_size(f.stat().st_size)
        log.info(f"  COPY {f.name} ({size})...")
        copy_csv(conn, stg_table, cols, f)

    # 4. Deduplicar staging
    log.info("  Deduplicando staging...")
    pk_cols = [c.strip() for c in pk.split(",")]
    dedup_cond = " AND ".join([f"a.{c} = b.{c}" for c in pk_cols])
    result = execute_with_count(conn, f"""
        DELETE FROM {stg_table} a USING {stg_table} b
        WHERE a.ctid < b.ctid AND {dedup_cond}
    """)
    log.info(f"  Duplicatas removidas: {result:,}")

    # 5. INSERT ou UPSERT
    if antes == 0:
        log.info("  INSERT direto (tabela vazia, sem índice GIN)...")
        execute(conn, f"INSERT INTO {target_table} ({cols}) SELECT {cols} FROM {stg_table};")
    else:
        log.info("  UPSERT (incremental)...")
        execute(conn, f"""
            INSERT INTO {target_table} ({cols})
            SELECT {cols} FROM {stg_table}
            ON CONFLICT ({pk}) DO UPDATE SET {config['upsert_set']}
        """)

    # 6. Dropar staging
    execute(conn, f"DROP TABLE {stg_table};")

    # 7. Recriar índices GIN
    for idx_sql in config["gin_indexes"]:
        idx_name = idx_sql.split("INDEX ")[1].split(" ON")[0]
        log.info(f"  Recriando índice {idx_name}...")
        execute(conn, idx_sql + ";")

    # 8. Estatísticas
    total = get_count(conn, nome)
    duracao = time.time() - t0
    novos = total - antes if total > antes else 0

    log_ingestao(conn, nome, antes, novos, total, duracao)
    log.info(f"  {nome.upper()}: antes={antes:,} novos={novos:,} total={total:,} ({duracao/60:.1f}min)")
    return total


def execute_with_count(conn, query):
    """Executa SQL e retorna rowcount."""
    with conn.cursor() as cur:
        cur.execute(query)
        count = cur.rowcount
        conn.commit()
        return count

# ── ETAPA 5: Resumo ─────────────────────────────────────────

def print_resumo(conn):
    """Imprime resumo de todas as tabelas."""
    log.info("=== RESUMO FINAL ===")
    tabelas = [
        "empresas", "estabelecimentos", "socios", "simples",
        "cnaes", "motivos", "municipios", "naturezas", "paises", "qualificacoes",
    ]
    for t in tabelas:
        try:
            count = get_count(conn, t)
            log.info(f"  {t:20s}: {count:>15,}")
        except Exception:
            log.info(f"  {t:20s}: (erro)")

# ── Main ─────────────────────────────────────────────────────

def main():
    parser = argparse.ArgumentParser(description="Ingestão CNPJ Receita Federal")
    parser.add_argument("--skip-extract", action="store_true", help="Pular extração dos ZIPs")
    parser.add_argument("--skip-tables", type=str, default="", help="Tabelas a pular (separadas por vírgula)")
    parser.add_argument("--skip-dominios", action="store_true", help="Pular tabelas de domínio")
    args = parser.parse_args()

    skip_tables = set(args.skip_tables.split(",")) if args.skip_tables else set()

    log.info("=" * 60)
    log.info("INÍCIO DA INGESTÃO")
    log.info("=" * 60)

    # Extração
    if args.skip_extract:
        log.info("Extração ignorada (--skip-extract)")
    else:
        extract_zips()

    # Conexão
    conn = get_conn()
    conn.autocommit = False

    try:
        # Schema
        conn.autocommit = True
        create_schema(conn)
        conn.autocommit = False

        # Domínios
        if not args.skip_dominios:
            conn.autocommit = True
            load_dominios(conn)
            conn.autocommit = False

        # Tabelas de dados
        for nome, config in TABELAS_DADOS.items():
            if nome in skip_tables:
                log.info(f"=== {nome.upper()}: PULADO (--skip-tables) ===")
                continue

            conn.autocommit = True
            load_tabela_dados(conn, nome, config)

        # ANALYZE
        log.info("Atualizando estatísticas...")
        conn.autocommit = True
        for t in TABELAS_DADOS:
            execute(conn, f"ANALYZE receita.{t};")

        # Resumo
        print_resumo(conn)

    finally:
        conn.close()

    log.info("=" * 60)
    log.info("INGESTÃO CONCLUÍDA")
    log.info("=" * 60)


if __name__ == "__main__":
    main()
