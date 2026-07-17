# SPIV — Sistema de Design e Identidade Visual (Design System)

Este documento registra as decisões de design, padrões visuais, paleta de cores e especificações CSS construídas para o SPIV, servindo como guia definitivo para que o padrão estético premium e mobile-first nunca seja perdido.

---

## 1. Princípios de Design
*   **Rich Aesthetics / Premium Feel:** Interfaces limpas, com sombras suaves, bordas arredondadas generosas (`12px` a `20px`) e transições suaves.
*   **Mobile-First:** Priorização para telas de smartphones (largura máxima sugerida de `480px` centralizado para a interface de vendas).
*   **Tipografia Moderna:** Uso da fonte **Inter** (do Google Fonts) ao invés das fontes padrão do navegador.

---

## 2. Paleta de Cores e Categorias (Clientes)

Cada categoria de cliente possui uma cor semântica dedicada. Essas cores devem ser usadas em bordas, gradientes, badges e botões de filtro ativo:

| Categoria | Cor (Hex) | Amostra HSL / Uso Recomendado |
| :--- | :--- | :--- |
| **BRONZE** | `#cd7f32` | HSL(30, 61%, 50%) — Bronze clássico |
| **PRATA** | `#8a8a8a` | HSL(0, 0%, 54%) — Cinza metálico médio |
| **OURO** | `#b8860b` | HSL(43, 89%, 38%) — Dourado escuro de alta legibilidade |
| **DIAMANTE** | `#185abc` | HSL(216, 77%, 41%) — Azul corporativo royal |
| **PLATINUM** | `#6b21a8` | HSL(273, 67%, 39%) — Roxo nobre |
| **INFINITE** | `#1e293b` | HSL(217, 33%, 17%) — Slate escuro/Preto premium |
| **CLUBE** | `#047857` | HSL(162, 94%, 24%) — Verde esmeralda escuro |

---

## 3. Especificação de Componentes

### 3.1. Chips de Filtro Rápido (Filter Chips)
Botões de canto arredondado (pills) para filtragem rápida na barra horizontal deslizante.

**CSS Padrão:**
```css
.filter-chip {
    flex-shrink: 0;
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 500;
    border: 1.5px solid #e5e7eb;
    background: #fff;
    color: #374151;
    cursor: pointer;
    transition: all .2s;
    white-space: nowrap;
}
```

**Customização de Cores por Categoria (Ativo & Hover):**
```css
/* Bronze */
.filter-chip[data-value="BRONZE"] { border-color: #cd7f32; color: #cd7f32; }
.filter-chip[data-value="BRONZE"].active { background: #cd7f32 !important; color: #fff !important; border-color: #cd7f32 !important; }
.filter-chip[data-value="BRONZE"]:hover:not(.active) { background: #cd7f32; color: #fff; border-color: #cd7f32; }

/* Ouro */
.filter-chip[data-value="OURO"] { border-color: #b8860b; color: #b8860b; }
.filter-chip[data-value="OURO"].active { background: #b8860b !important; color: #fff !important; border-color: #b8860b !important; }
.filter-chip[data-value="OURO"]:hover:not(.active) { background: #b8860b; color: #fff; border-color: #b8860b; }

/* Prata */
.filter-chip[data-value="PRATA"] { border-color: #8a8a8a; color: #8a8a8a; }
.filter-chip[data-value="PRATA"].active { background: #8a8a8a !important; color: #fff !important; border-color: #8a8a8a !important; }
.filter-chip[data-value="PRATA"]:hover:not(.active) { background: #8a8a8a; color: #fff; border-color: #8a8a8a; }

/* Diamante */
.filter-chip[data-value="DIAMANTE"] { border-color: #185abc; color: #185abc; }
.filter-chip[data-value="DIAMANTE"].active { background: #185abc !important; color: #fff !important; border-color: #185abc !important; }
.filter-chip[data-value="DIAMANTE"]:hover:not(.active) { background: #185abc; color: #fff; border-color: #185abc; }

/* Platinum */
.filter-chip[data-value="PLATINUM"] { border-color: #6b21a8; color: #6b21a8; }
.filter-chip[data-value="PLATINUM"].active { background: #6b21a8 !important; color: #fff !important; border-color: #6b21a8 !important; }
.filter-chip[data-value="PLATINUM"]:hover:not(.active) { background: #6b21a8; color: #fff; border-color: #6b21a8; }

/* Infinite */
.filter-chip[data-value="INFINITE"] { border-color: #1e293b; color: #1e293b; }
.filter-chip[data-value="INFINITE"].active { background: #1e293b !important; color: #fff !important; border-color: #1e293b !important; }
.filter-chip[data-value="INFINITE"]:hover:not(.active) { background: #1e293b; color: #fff; border-color: #1e293b; }

/* Clube */
.filter-chip[data-value="CLUBE"] { border-color: #047857; color: #047857; }
.filter-chip[data-value="CLUBE"].active { background: #047857 !important; color: #fff !important; border-color: #047857 !important; }
.filter-chip[data-value="CLUBE"]:hover:not(.active) { background: #047857; color: #fff; border-color: #047857; }
```

### 3.2. Cards de Clientes (Swipe Cards)
Usados na interface mobile-first do vendedor para exibir os principais dados cadastrais com gesto de deslizar (swipe).

**Estrutura HTML do Banner superior do card:**
```html
<div class="card-banner" style="background: linear-gradient(135deg, <?= $catColor ?>, <?= $catColor ?>dd);">
    <span class="cat-name"><?= esc($cliente['categoria']) ?></span>
    <span class="cat-badge"><?= esc($cliente['ciclo_de_vida']) ?></span>
</div>
```

---

## 4. Manutenção de Modificações Visuais

*   Qualquer nova tela ou componente de categorização de clientes criado deve ler este documento e adotar estritamente o mapa de cores `CAT_COLORS`.
*   Modificações diretas nas folhas de estilo de componentes sem manter os fallbacks declarados violarão a identidade visual construída.
