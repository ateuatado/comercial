import psycopg2
import sys
cfg = {"host":"localhost","port":5432,"dbname":"spiv","user":"postgres","password":"LulaTetra26"}
try:
	conn = psycopg2.connect(**cfg)
	cur = conn.cursor()
	cur.execute('SELECT COUNT(*) FROM receita.socios')
	print(cur.fetchone()[0])
	cur.close()
	conn.close()
except Exception as e:
	print('ERROR:', e, file=sys.stderr)
	sys.exit(1)
