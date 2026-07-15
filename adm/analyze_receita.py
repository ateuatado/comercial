import psycopg2
from datetime import datetime
cfg = {"host":"localhost","port":5432,"dbname":"spiv","user":"postgres","password":"LulaTetra26"}
outfile = 'adm/analyze_output.txt'
with open(outfile,'w',encoding='utf-8') as fh:
    fh.write(f'Analyze run at {datetime.now().isoformat()}\n')
    conn = psycopg2.connect(**cfg)
    cur = conn.cursor()
    cur.execute("SELECT table_name FROM information_schema.tables WHERE table_schema='receita' AND table_type='BASE TABLE'")
    tables = [r[0] for r in cur.fetchall()]
    for t in tables:
        try:
            fh.write(f'ANALYZE receita.{t}...\n')
            cur.execute(f'ANALYZE receita."{t}"')
            conn.commit()
            fh.write(f'OK: {t}\n')
        except Exception as e:
            fh.write(f'ERROR {t}: {e}\n')
    cur.close(); conn.close()
print('WROTE', outfile)
