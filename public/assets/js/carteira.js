/**
 * SPIV — carteira.js
 * Comportamento do portal operacional do ACOM / Gerente de Conta.
 *
 * Responsabilidade única: orquestrar o modal de atualização de status,
 * populando os campos com base nos atributos data-* do botão acionador.
 *
 * Depende de: Bootstrap 5 (Modal), main.js (tooltips globais)
 */

/* Mapa de transições permitidas para ACOM / Gerente de Conta (espelha servidor) */
const SPIV_STATUS_TRANSITIONS = {
    novo:              ['em_acompanhamento', 'sem_contato'],
    em_acompanhamento: ['sem_contato', 'convertido'],
    sem_contato:       ['em_acompanhamento'],
    convertido:        [],
    bloqueado:         [],
    inativo:           [],
};

/* Labels legíveis por humanos */
const SPIV_STATUS_LABELS = {
    novo:              'Novo',
    em_acompanhamento: 'Em Acompanhamento',
    convertido:        'Convertido',
    sem_contato:       'Sem Contato',
    bloqueado:         'Bloqueado',
    inativo:           'Inativo',
};

document.addEventListener('DOMContentLoaded', () => {
    const modalEl = document.getElementById('modal-status');
    if (!modalEl) return;

    modalEl.addEventListener('show.bs.modal', (event) => {
        const trigger     = event.relatedTarget;
        const cnpj        = trigger.dataset.cnpj;
        const statusAtual = trigger.dataset.status;

        /* Preenche campos de leitura */
        modalEl.querySelector('#modal-cnpj').value        = cnpj;
        modalEl.querySelector('#modal-status-atual').value = SPIV_STATUS_LABELS[statusAtual] ?? statusAtual;

        /* Popula select com transições permitidas */
        const select = modalEl.querySelector('#modal-status-novo');
        select.innerHTML = '<option value="">— Selecione —</option>';

        const transicoes = SPIV_STATUS_TRANSITIONS[statusAtual] ?? [];
        transicoes.forEach((s) => {
            const opt = document.createElement('option');
            opt.value       = s;
            opt.textContent = SPIV_STATUS_LABELS[s] ?? s;
            select.appendChild(opt);
        });
    });
});
