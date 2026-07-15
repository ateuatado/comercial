import psycopg2
cfg = {"host":"localhost","port":5432,"dbname":"spiv","user":"postgres","password":"LulaTetra26"}
conn = psycopg2.connect(**cfg)
cur = conn.cursor()
for t in ['empresas','estabelecimentos','socios','simples','cnaes','municipios']:
    try:
        cur.execute(f"SELECT COUNT(*) FROM receita.{t}")
        print(f"{t}: {cur.fetchone()[0]}")
    except Exception as e:
        print(f"{t}: ERROR: {e}")
cur.close(); conn.close()
