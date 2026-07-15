/**
 * SPIV — prospecting.js
 * Comportamento do módulo de prospecção antifraude (admin).
 *
 * Responsabilidades:
 *  - Confirmação antes de submeter decisão de revisão (liberado/rejeitado).
 *  - Filtro de status na listagem (show/hide por classe).
 *  - Nenhuma lógica de negócio — apenas UX e navegação.
 *
 * Depende de: Bootstrap 5, main.js (tooltips globais)
 */

document.addEventListener('DOMContentLoaded', () => {

    /* ── Confirmação de decisão (formulário de revisão) ──────── */
    const reviewForm = document.getElementById('form-review');
    if (reviewForm) {
        reviewForm.addEventListener('submit', (e) => {
            const decisao = reviewForm.querySelector('[name="decisao"]:checked')?.value;
            const label   = decisao === 'liberado' ? 'LIBERAR' : 'REJEITAR';
            const msg     = `Confirmar decisão: ${label} esta suspeita?\nEsta ação ficará registrada no histórico.`;
            if (!window.confirm(msg)) {
                e.preventDefault();
            }
        });
    }

    /* ── Filtro de status na listagem ────────────────────────── */
    const filterBtns = document.querySelectorAll('[data-filter-status]');
    const flagRows   = document.querySelectorAll('[data-row-status]');

    filterBtns.forEach((btn) => {
        btn.addEventListener('click', () => {
            const target = btn.dataset.filterStatus;

            /* Marca botão ativo */
            filterBtns.forEach((b) => b.classList.remove('active'));
            btn.classList.add('active');

            /* Mostra/oculta linhas */
            flagRows.forEach((row) => {
                const match = target === 'todos' || row.dataset.rowStatus === target;
                row.classList.toggle('d-none', !match);
            });
        });
    });

    /* ── Formatação de CPF/CNPJ nos inputs ───────────────────── */
    const maskDigitsOnly = (el, maxLen) => {
        el.addEventListener('input', () => {
            el.value = el.value.replace(/\D/g, '').slice(0, maxLen);
        });
    };

    const cpfInput  = document.getElementById('input-cpf-socio');
    const cnpjInput = document.getElementById('input-cnpj');
    const cnpjRelInput = document.getElementById('input-cnpj-relacionado');

    if (cpfInput)     maskDigitsOnly(cpfInput, 11);
    if (cnpjInput)    maskDigitsOnly(cnpjInput, 14);
    if (cnpjRelInput) maskDigitsOnly(cnpjRelInput, 14);
});
