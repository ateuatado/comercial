"""
importar_negociacoes.py
=======================
Importa adm/negociacoes.csv para as tabelas:
  - plano_de_vendas  (20 ações PV 2026)
  - negociacoes      (22.433 registros do SAD)

Requer: psycopg2  (pip install psycopg2-binary)
Uso:    python scratch/importar_negociacoes.py
"""

import csv, re, os, sys
from datetime import datetime
import psycopg2
from psycopg2.extras import execute_values

# ── Configuração do banco ────────────────────────────────────────────────────
DB = dict(
    host    = "localhost",
    port    = 5432,
    dbname  = "spivvps",
    user    = "postgres",
    password= "LulaTetra26",
    client_encoding = "utf8",
)
CSV_PATH = r"adm\negociacoes.csv"

# ── Dados do Plano de Vendas (Apêndice I do PDF) ────────────────────────────
PLANO = [
    ("#PV2026MASTER",            "MASTER",             2026,
     "Usar Tabela Master para crescer receita e volume em clientes com potencial Diamante ou Infinite, "
     "vinculando condição comercial a compromisso mínimo de faturamento. Mapear pelo SAD o potencial do "
     "cliente, identificar condições críticas para formalização da tabela e negociar tabela vinculada a volume.",
     "Aumentar faturamento recorrente; Ganhar share; Dar previsibilidade à receita."),

    ("#PV2026UPGRADE",           "UPGRADE",            2026,
     "Evoluir o pacote comercial do cliente, ampliando escopo ou volume. Mapear pelo SAD as oportunidades "
     "de aumento de carga e pela Ferramenta de Análise de Resultados o estágio do cliente; Propor ampliação "
     "de escopo e benefícios; Formalizar novo enquadramento.",
     "Aumentar receita média por cliente; Elevar categoria do cliente; Fortalecer relacionamento comercial."),

    ("#PV2026AUMENTODESHARE",    "AUMENTODESHARE",     2026,
     "Ampliar a participação dos Correios no volume total do cliente, aumentando a relevância da Empresa na "
     "operação logística. Mapear pelo SAD o volume operado por concorrência ou estrutura própria; Identificar "
     "fluxos com maior viabilidade de migração; Negociar migração parcial de carga com metas progressivas.",
     "Aumentar ou recuperar participação na operação do cliente; Ampliar volume postado; Fortalecer a presença "
     "dos Correios na carteira."),

    ("#PV2026PUDO",              "PUDO",               2026,
     "Implantar soluções de PUDO para ampliar eficiência de entrega e conveniência ao destinatário. "
     "Mapear região com alto insucesso de entrega; Propor lockers ou clique e retire.",
     "Mitigar possíveis perdas; Ampliar pontos de distribuição; Melhorar experiência do cliente."),

    ("#PV2026LOG",               "LOG",                2026,
     "Estruturar projetos como solução para carga consolidada, posicionando os Correios como operador "
     "logístico do cliente. Mapear fluxos, volumes e sazonalidade; Identificar carga consolidada operada "
     "por concorrência; Construir proposta integrada com áreas técnicas.",
     "Expandir portfólio de serviços; Aumentar receita logística; Consolidar os Correios como operador."),

    ("#PV2026LOG+",              "LOG+",               2026,
     "Ofertar LOG+ (fulfillment dos Correios) como solução integrada para clientes com operação de e-commerce. "
     "Identificar volumes, mix de produtos e sazonalidade; Propor migração total ou parcial da operação para "
     "o LOG+; Articular com CLIs.",
     "Implantar contrato de fulfillment; Aumentar receita logística; Consolidar os Correios como parceiro "
     "logístico estratégico."),

    ("#PV2026LOGSUPRI",          "LOGSUPRI",           2026,
     "Assumir a gestão logística de suprimentos do cliente, garantindo disponibilidade de materiais, insumos "
     "ou produtos no momento certo e no local correto (LogSupri e Supri InHouse). Identificar clientes, mapear "
     "cadeia logística, ofertar serviço e articular com áreas operacionais.",
     "Garantir continuidade do abastecimento; Reduzir custos do cliente; Gerar receita logística previsível."),

    ("#PV2026LOGREVERSA",        "LOGREVERSA",         2026,
     "Ampliar a relação comercial com clientes que utilizam apenas logística reversa, expandindo para postagem "
     "ativa. Identificar oportunidades no SAD; Ofertar serviço de postagem (SEDEX e PAC); Propor ampliação de "
     "escopo e benefícios.",
     "Aumentar receita no cliente; Expandir uso do portfólio; Evoluir relacionamento comercial."),

    ("#PV2026COMUNICACAO",       "COMUNICACAO",        2026,
     "Assumir o envio de cobrança e correspondência no âmbito do monopólio postal para envio de tributos e "
     "notificações. Mapear clientes com demandas de comunicação oficial; Levantar volumes, prazos legais e "
     "calendário de envios; Ofertar serviço.",
     "Aumentar a arrecadação do cliente; Garantir validade legal das comunicações; Gerar receita postal "
     "recorrente no segmento de monopólio."),

    ("#PV2026NOVOMALOTE",        "NOVOMALOTE",         2026,
     "Modernizar circulação de documentos com Malote 2.0, que inclui rastreabilidade, SLAs definidos e "
     "integração digital. Mapear clientes que usam malote tradicional ou da concorrência; Propor migração "
     "para novo malote ou contratação.",
     "Manter ou recuperar receita; Melhorar eficiência operacional; Aumentar valor agregado."),

    ("#PV2026GESTAODOC",         "GESTAODOC",          2026,
     "Estruturar soluções completas de gestão documental para clientes com acervo físico: coleta, transporte, "
     "digitalização, indexação e custódia. Identificar clientes; Propor solução; Mapear o acervo; Desenhar a "
     "operação (RCN e FMOE).",
     "Gerar receita de longo prazo; Ampliar escopo de serviços; Fidelizar cliente."),

    ("#PV2026LOGSAUDE",          "LOGSAUDE",           2026,
     "Atuar como operador logístico para a saúde pública e privada, com solução completa ou parcial, iniciando "
     "pela distribuição com possibilidade de ampliação gradual. Mapear modelo logístico atual do cliente; "
     "Identificar exigências contratuais e regulatórias; Definir entrada pela distribuição.",
     "Gerar receita; Expandir uso do portfólio; Evoluir relacionamento comercial; Garantir expansão do "
     "serviço de LogSaúde."),

    ("#PV2026ELEICOES",          "ELEICOES",           2026,
     "Prospectar negócios institucionais do processo eleitoral 2026, com foco em logística de urnas, "
     "equipamentos, materiais administrativos e comunicações oficiais (excluídas ações de marketing "
     "eleitoral de candidatos). Mapear oportunidades junto ao TRE e órgãos de apoio.",
     "Implantar contratos institucionais; Gerar receita; Consolidar os Correios como operador logístico "
     "do processo eleitoral."),

    ("#PV2026IPTU",              "IPTU",               2026,
     "Viabilizar envio de IPTU e demais tributos municipais. Prospectar municípios; Mapear volumes e prazos; "
     "Articular com área operacional.",
     "Gerar receita recorrente; Expandir carteira governamental."),

    ("#PV2026BALCAO",            "BALCAO",             2026,
     "Expandir atuação dos Correios como ponto de acesso a serviços públicos (Balcão do Cidadão). "
     "Identificar demandas; Articular com entes públicos; Formalizar adesão.",
     "Ampliar serviços; Gerar receita; Fortalecer presença local."),

    ("#PV2026OPERACOESESPECIAIS","OPERACOESESPECIAIS",  2026,
     "Atender demandas logísticas customizadas, não padronizadas ou de caráter excepcional. Mapear "
     "necessidades especiais do cliente; Desenhar a operação (RCN e FMOE); Estruturar proposta logística "
     "aderente ao contrato.",
     "Atender projetos estratégicos; Vislumbrar novos mercados; Gerar receita no segmento de logística."),

    ("#PV2026MEXPO",             "MEXPO",              2026,
     "Estimular exportação via Correios. Identificar clientes com potencial exportador; Orientar modelo "
     "MEXPO; Formalizar ampliação de volumes.",
     "Aumentar receita internacional; Ampliar base exportadora."),

    ("#PV2026BLACK",             "BLACK",              2026,
     "Ampliar carga no período da Black Friday. Planejar com antecedência; Negociar aumento de volume; "
     "Formalizar capacidade.",
     "Aumentar receita sazonal; Maximizar pico de demanda."),

    ("#PV2026COPA",              "COPA",               2026,
     "Aproveitar oportunidades comerciais da Copa do Mundo 2026 — e-commerce, entidades esportivas "
     "(ex.: CBF), patrocinadores e parceiros oficiais. Identificar clientes impactados; Mapear volumes "
     "e janelas de demanda; Articular com áreas técnicas para dimensionar capacidade.",
     "Aumentar receita pontual; Expandir uso do portfólio; Ampliar visibilidade institucional dos "
     "Correios; Aproveitamento estratégico de mercado."),

    ("#PV2026REMESSA",           "REMESSA",            2026,
     "Recuperar o envio de remessa de documentos para clientes públicos e privados. Focar em cartões "
     "bancários, carteiras de conselhos profissionais e documentos oficiais (Passaporte, RG, CNH). "
     "Identificar volumes, periodicidade, prazos e requisitos de segurança; Ofertar serviço.",
     "Aumentar a arrecadação do cliente; Fortalecer relacionamento institucional."),
]

# ── Helpers ──────────────────────────────────────────────────────────────────
def parse_brl(s):
    """'R$ 8.472,99' → 8472.99 | '' → None"""
    if not s or not s.strip():
        return None
    s = s.strip().replace('R$', '').replace(' ', '').replace('.', '').replace(',', '.')
    try:
        return float(s)
    except ValueError:
        return None

def parse_date(s):
    """'23/03/2026' → '2026-03-23' | '' → None"""
    if not s or not s.strip():
        return None
    try:
        return datetime.strptime(s.strip(), '%d/%m/%Y').date().isoformat()
    except ValueError:
        return None

def parse_int(s):
    if not s or not s.strip():
        return None
    try:
        return int(s.strip())
    except ValueError:
        return None

# ── Import principal ─────────────────────────────────────────────────────────
def main():
    conn = psycopg2.connect(**DB)
    cur  = conn.cursor()

    # 1. plano_de_vendas
    print("Inserindo plano_de_vendas...")
    pv_rows = [(h, n, a, d, o) for h, n, a, d, o in PLANO]
    execute_values(cur,
        """INSERT INTO plano_de_vendas (hashtag, nome_da_acao, ano, detalhe_da_acao, objetivo_da_acao)
           VALUES %s ON CONFLICT (hashtag) DO NOTHING""",
        pv_rows
    )
    conn.commit()
    cur.execute("SELECT COUNT(*) FROM plano_de_vendas")
    print(f"  plano_de_vendas: {cur.fetchone()[0]} registros")

    # 2. negociacoes
    print(f"\nLendo {CSV_PATH}...")
    rows = []
    with open(CSV_PATH, encoding='iso-8859-1', newline='') as f:
        reader = csv.DictReader(f, delimiter=';')
        for raw in reader:
            row = {k.strip().lstrip('\ufeff'): v for k, v in raw.items()}
            hashtag = (row.get('HASHTAG') or '').strip()
            if not hashtag.startswith('#PV'):
                hashtag = None

            rows.append((
                parse_int(row.get('NEGOCIA\u00c7\u00c3O ID') or row.get('NEGOCIAÇÃO ID') or ''),
                (row.get('GRUPO CLIENTE') or '').strip() or None,
                hashtag,
                (row.get('NEGOCIA\u00c7\u00c3O DESCRI\u00c7\u00c3O') or
                 row.get('NEGOCIAÇÃO DESCRIÇÃO') or '').strip() or None,
                (row.get('STATUS') or '').strip() or None,
                (row.get('RESULTADO') or '').strip() or None,
                parse_brl(row.get('REC. PREVISTA') or ''),
                parse_brl(row.get('REC. REALIZADA') or ''),
                parse_int(row.get('NEG. V\u00c1LIDA') or row.get('NEG. VÁLIDA') or ''),
                (row.get('FOR\u00c7A DE VENDAS') or row.get('FORÇA DE VENDAS') or '').strip() or None,
                (row.get('DETALHE NEG V\u00c1LIDA') or row.get('DETALHE NEG VÁLIDA') or '').strip() or None,
                parse_date(row.get('DATA CADASTRO') or ''),
                (row.get('IN\u00cdCIO PREVI. (R$)') or row.get('INÍCIO PREVI. (R$)') or '').strip() or None,
                (row.get('\u00daCTIMO REAL. (R$)') or row.get('ÚLTIMO REAL. (R$)') or '').strip() or None,
                (row.get('TIPO') or '').strip() or None,
                (row.get('SEGMENTO') or '').strip() or None,
            ))

    print(f"  CSV lido: {len(rows)} linhas. Inserindo no banco...")
    BATCH = 500
    inserted = 0
    for i in range(0, len(rows), BATCH):
        batch = rows[i:i+BATCH]
        execute_values(cur,
            """INSERT INTO negociacoes
               (negociacao_id, grupo_cliente, hashtag, descricao, status, resultado,
                rec_prevista, rec_realizada, neg_valida, forca_de_vendas, detalhe_neg_valida,
                data_cadastro, inicio_previsto, ultimo_realizado, tipo, segmento)
               VALUES %s ON CONFLICT (negociacao_id) DO NOTHING""",
            batch
        )
        inserted += len(batch)
        print(f"  {inserted}/{len(rows)} inseridos...", end='\r')
    conn.commit()

    cur.execute("SELECT COUNT(*) FROM negociacoes")
    total = cur.fetchone()[0]
    print(f"\n\n✅ negociacoes: {total} registros no banco")

    # 3. Resumo por ação
    print("\n── Distribuição por ação ─────────────────────────────────────")
    cur.execute("""
        SELECT pv.nome_da_acao, COUNT(n.id) AS total,
               SUM(n.rec_prevista) AS prevista,
               SUM(n.rec_realizada) AS realizada
        FROM plano_de_vendas pv
        LEFT JOIN negociacoes n ON n.hashtag = pv.hashtag
        GROUP BY pv.nome_da_acao ORDER BY total DESC
    """)
    for r in cur.fetchall():
        prev = f"R${r[2]:,.2f}" if r[2] else "—"
        real = f"R${r[3]:,.2f}" if r[3] else "—"
        print(f"  {r[0]:<22} {r[1]:>6} neg | prevista {prev:>18} | realizada {real}")

    cur.close()
    conn.close()
    print("\n✅ Import concluído.")

if __name__ == '__main__':
    main()
