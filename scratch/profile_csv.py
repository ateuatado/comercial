import csv, json, re, sys
from collections import Counter

path = r'adm\negociacoes.csv'

# Detecta e lê com latin-1
rows = []
with open(path, encoding='iso-8859-1', newline='') as f:
    reader = csv.DictReader(f, delimiter=';')
    for row in reader:
        rows.append(row)

# Normaliza keys (remove espaços/BOM)
def clean_key(k):
    return k.strip().lstrip('\ufeff')

rows = [{clean_key(k): v for k, v in r.items()} for r in rows]

print(f"Total linhas: {len(rows)}")
print(f"\nColunas: {list(rows[0].keys())}")

# Hashtags distintas válidas
hashtags_raw = [r.get('HASHTAG', '').strip() for r in rows]
hashtags = sorted(set(h for h in hashtags_raw if h.startswith('#PV')))
print(f"\nHashtags distintas ({len(hashtags)}):")
for h in hashtags:
    print(f"  {h}")

# Parse #PVAAAAMOME
print("\nParse das hashtags:")
for h in hashtags:
    m = re.match(r'^#PV(\d{4})(.+)$', h)
    if m:
        print(f"  {h!r:35} -> ano={m.group(1)}, nome_da_acao={m.group(2)}")

# Status distintos
status_vals = sorted(set(r.get('STATUS','').strip() for r in rows if r.get('STATUS','').strip()))
print(f"\nSTATUS distintos: {status_vals}")

# Resultado
resultado_vals = sorted(set(r.get('RESULTADO','').strip() for r in rows if r.get('RESULTADO','').strip()))
print(f"\nRESULTADO distintos: {resultado_vals}")

# Tipo
tipo_vals = sorted(set(r.get('TIPO','').strip() for r in rows if r.get('TIPO','').strip()))
print(f"\nTIPO distintos: {tipo_vals}")

# Segmento
seg_vals = sorted(set(r.get('SEGMENTO','').strip() for r in rows if r.get('SEGMENTO','').strip()))
print(f"\nSEGMENTO distintos: {seg_vals}")

# Receita prevista — exemplos
rec = [r.get('REC. PREVISTA','').strip() for r in rows[:5]]
print(f"\nExemplos REC. PREVISTA: {rec}")

# Neg válida
nv = sorted(set(r.get('NEG. VÁLIDA','').strip() for r in rows if r.get('NEG. VÁLIDA','').strip()))
print(f"\nNEG. VÁLIDA distintos: {nv}")
