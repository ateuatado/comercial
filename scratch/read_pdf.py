import pdfplumber, sys, io

sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8', errors='replace')

pdf_path = r'adm\plano_de_vendas_2026_vf_09_01_2026.pdf'

with pdfplumber.open(pdf_path) as pdf:
    total = len(pdf.pages)
    print(f"Total de paginas: {total}\n")
    print("=" * 60)
    for i, page in enumerate(pdf.pages):
        text = page.extract_text()
        if text and text.strip():
            print(f"\n--- PAGINA {i+1} ---")
            print(text.strip())
        else:
            print(f"\n--- PAGINA {i+1} [sem texto extraivel] ---")
