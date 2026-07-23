<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<style>
.nota-container {
    max-width: 480px;
    margin: 0 auto;
    background: #f0f2f5;
    min-height: 100vh;
}
.nota-topbar {
    display: flex; align-items: center; gap: 12px;
    padding: 12px 16px; background: #fff;
    border-bottom: 1px solid #e5e7eb;
    position: sticky; top: 0; z-index: 100;
}
.nota-topbar .back-btn {
    width: 36px; height: 36px; border-radius: 50%;
    background: #f3f4f6; border: none;
    display: flex; align-items: center; justify-content: center;
    font-size: 18px; color: #374151; cursor: pointer;
}
.nota-topbar .back-btn:hover { background: #e5e7eb; }
.nota-topbar h6 { margin: 0; font-weight: 700; font-size: 15px; }

/* Client mini card */
.client-mini {
    margin: 16px; padding: 14px 16px;
    background: #fff; border-radius: 14px;
    box-shadow: 0 1px 4px rgba(0,0,0,.04);
    display: flex; align-items: center; gap: 12px;
}
.client-mini .avatar {
    width: 44px; height: 44px; border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    color: #fff; font-size: 18px; font-weight: 800;
    flex-shrink: 0;
}
.client-mini .info .name {
    font-size: 14px; font-weight: 700; color: #1e293b;
    line-height: 1.2; margin-bottom: 2px;
}
.client-mini .info .sub {
    font-size: 11px; color: #94a3b8;
}

/* Form */
.nota-form {
    padding: 0 16px 16px;
}
.form-section {
    background: #fff; border-radius: 14px;
    padding: 18px; margin-bottom: 12px;
    box-shadow: 0 1px 4px rgba(0,0,0,.04);
}
.form-section label {
    display: block; font-size: 11px; font-weight: 700;
    text-transform: uppercase; letter-spacing: .5px;
    color: #64748b; margin-bottom: 10px;
}

/* Tipo selector */
.tipo-grid {
    display: grid; grid-template-columns: 1fr 1fr;
    gap: 8px;
}
.tipo-option {
    display: flex; align-items: center; gap: 8px;
    padding: 10px 12px; border-radius: 10px;
    border: 2px solid #e5e7eb; background: #fff;
    cursor: pointer; transition: all .2s;
}
.tipo-option:hover { border-color: #93c5fd; }
.tipo-option.selected { border-color: #1e40af; background: #eff6ff; }
.tipo-option input { display: none; }
.tipo-dot {
    width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0;
}
.tipo-dot.visita { background: #22c55e; }
.tipo-dot.observacao { background: #3b82f6; }
.tipo-dot.contato_telefonico { background: #f59e0b; }
.tipo-dot.reuniao { background: #8b5cf6; }
.tipo-dot.estrategia { background: #ef4444; }
.tipo-label {
    font-size: 12px; font-weight: 600; color: #374151;
}

/* Textarea */
.nota-textarea {
    width: 100%; min-height: 140px; padding: 14px;
    border: 2px solid #e5e7eb; border-radius: 12px;
    font-size: 14px; line-height: 1.5; resize: vertical;
    font-family: 'Inter', system-ui, sans-serif;
    transition: border-color .2s; outline: none;
    background: #fafbfc;
}
.nota-textarea:focus {
    border-color: #3b82f6; background: #fff;
}
.char-count {
    text-align: right; font-size: 11px; color: #94a3b8;
    margin-top: 4px;
}

/* Sentiment */
.sentiment-row {
    display: flex; gap: 0; justify-content: center;
}
.sentiment-btn {
    flex: 1; padding: 12px; border: 2px solid #e5e7eb;
    background: #fff; cursor: pointer; transition: all .2s;
    display: flex; flex-direction: column; align-items: center;
    gap: 4px;
}
.sentiment-btn:first-child { border-radius: 12px 0 0 12px; }
.sentiment-btn:last-child { border-radius: 0 12px 12px 0; }
.sentiment-btn:not(:last-child) { border-right: none; }
.sentiment-btn .emoji { font-size: 28px; transition: transform .2s; }
.sentiment-btn .slabel { font-size: 10px; color: #94a3b8; font-weight: 600; }
.sentiment-btn:hover .emoji { transform: scale(1.2); }
.sentiment-btn.selected { border-color: #1e40af; background: #eff6ff; }
.sentiment-btn.selected .emoji { transform: scale(1.15); }
.sentiment-btn.selected .slabel { color: #1e40af; }
.sentiment-btn input { display: none; }

/* Submit */
.submit-section {
    padding: 0 16px 24px;
}
.submit-btn {
    width: 100%; padding: 14px; border-radius: 14px;
    border: none; font-size: 15px; font-weight: 700;
    cursor: pointer; transition: all .2s;
    display: flex; align-items: center; justify-content: center; gap: 8px;
}
.submit-btn.primary {
    background: linear-gradient(135deg, #1e40af, #3b82f6);
    color: #fff; box-shadow: 0 4px 14px rgba(30,64,175,.3);
}
.submit-btn.primary:hover {
    box-shadow: 0 6px 20px rgba(30,64,175,.4);
    transform: translateY(-1px);
}
.submit-btn:disabled {
    opacity: .6; cursor: not-allowed; transform: none !important;
}

/* Dev badge */
.dev-badge {
    display: flex; align-items: center; gap: 8px;
    padding: 10px 14px; border-radius: 10px;
    background: #fef3c7; border: 1px solid #fde68a;
    font-size: 12px; color: #92400e; margin-top: 10px;
}
.dev-badge i { font-size: 16px; }

/* Toast */
.toast-msg {
    position: fixed; bottom: 80px; left: 50%; transform: translateX(-50%);
    background: #1e293b; color: #fff; padding: 12px 24px;
    border-radius: 12px; font-size: 13px; font-weight: 600;
    box-shadow: 0 4px 16px rgba(0,0,0,.2);
    opacity: 0; transition: opacity .3s; pointer-events: none;
    z-index: 999; max-width: 320px; text-align: center;
}
.toast-msg.show { opacity: 1; }
</style>

<?php
    $catColors = [
        'BRONZE'=>'#cd7f32','OURO'=>'#b8860b','PRATA'=>'#8a8a8a',
        'DIAMANTE'=>'#185abc','PLATINUM'=>'#6b21a8','INFINITE'=>'#1e293b','CLUBE'=>'#047857'
    ];
    $cat = strtoupper($cliente['categoria'] ?? '');
    $catColor = $catColors[$cat] ?? '#64748b';
    $initial = mb_substr($cliente['razao_social'] ?? '?', 0, 1);

    $cnpj = $cliente['cnpj'] ?? '';
    $cnpjFmt = strlen($cnpj) === 14
        ? substr($cnpj,0,2).'.'.substr($cnpj,2,3).'.'.substr($cnpj,5,3).'/'.substr($cnpj,8,4).'-'.substr($cnpj,12,2)
        : $cnpj;
?>

<div class="nota-container">

    <!-- Top bar -->
    <div class="nota-topbar">
        <a href="<?= site_url('vendedor/cliente/' . $cliente['cnpj']) ?>" class="back-btn">
            <i class="bi bi-arrow-left"></i>
        </a>
        <h6>Registrar Nota</h6>
    </div>

    <!-- Client mini card -->
    <div class="client-mini">
        <div class="avatar" style="background: <?= $catColor ?>;"><?= esc($initial) ?></div>
        <div class="info">
            <div class="name"><?= esc($cliente['razao_social'] ?? '—') ?></div>
            <div class="sub"><?= $cnpjFmt ?> · <?= esc($cliente['segmento_mercado'] ?? '') ?></div>
        </div>
    </div>

    <!-- Form -->
    <form class="nota-form" id="notaForm" onsubmit="return false;">

        <!-- Tipo -->
        <div class="form-section">
            <label><i class="bi bi-tag"></i> Tipo de Registro</label>
            <div class="tipo-grid">
                <label class="tipo-option selected">
                    <input type="radio" name="tipo" value="visita" checked>
                    <span class="tipo-dot visita"></span>
                    <span class="tipo-label">Visita</span>
                </label>
                <label class="tipo-option">
                    <input type="radio" name="tipo" value="contato_telefonico">
                    <span class="tipo-dot contato_telefonico"></span>
                    <span class="tipo-label">Contato</span>
                </label>
                <label class="tipo-option">
                    <input type="radio" name="tipo" value="reuniao">
                    <span class="tipo-dot reuniao"></span>
                    <span class="tipo-label">Reunião</span>
                </label>
                <label class="tipo-option">
                    <input type="radio" name="tipo" value="observacao">
                    <span class="tipo-dot observacao"></span>
                    <span class="tipo-label">Observação</span>
                </label>
                <label class="tipo-option" style="grid-column: span 2;">
                    <input type="radio" name="tipo" value="estrategia">
                    <span class="tipo-dot estrategia"></span>
                    <span class="tipo-label">Nota de Estratégia</span>
                </label>
            </div>
        </div>

        <!-- Conteúdo -->
        <div class="form-section">
            <label><i class="bi bi-pencil"></i> Descrição</label>
            <textarea class="nota-textarea" id="notaConteudo" name="conteudo"
                      placeholder="Descreva a visita, observação ou contato..."
                      maxlength="2000"></textarea>
            <div class="char-count"><span id="charCount">0</span>/2000</div>
        </div>

        <!-- Sentimento -->
        <div class="form-section">
            <label><i class="bi bi-emoji-smile"></i> Como foi a interação?</label>
            <div class="sentiment-row">
                <label class="sentiment-btn">
                    <input type="radio" name="sentimento" value="positivo">
                    <span class="emoji">😊</span>
                    <span class="slabel">Positivo</span>
                </label>
                <label class="sentiment-btn selected">
                    <input type="radio" name="sentimento" value="neutro" checked>
                    <span class="emoji">😐</span>
                    <span class="slabel">Neutro</span>
                </label>
                <label class="sentiment-btn">
                    <input type="radio" name="sentimento" value="negativo">
                    <span class="emoji">😟</span>
                    <span class="slabel">Negativo</span>
                </label>
            </div>
        <!-- Visibilidade -->
        <div class="form-section">
            <label><i class="bi bi-eye"></i> Visibilidade</label>
            <label style="display:flex;align-items:center;gap:14px;cursor:pointer;padding:10px 14px;border:2px solid #86efac;border-radius:12px;background:#fafbfc;transition:border-color .2s;" id="visibilidadeToggleWrap">
                <div style="position:relative;flex-shrink:0;">
                    <input type="checkbox" id="chkPublica" name="publica" value="1" checked="checked" style="opacity:0;width:0;height:0;position:absolute;">
                    <div id="toggleTrack" style="width:44px;height:24px;border-radius:99px;background:#22c55e;transition:background .2s;position:relative;">
                        <div id="toggleThumb" style="width:20px;height:20px;border-radius:50%;background:#fff;box-shadow:0 1px 4px rgba(0,0,0,.2);position:absolute;top:2px;left:22px;transition:left .2s;"></div>
                    </div>
                </div>
                <div>
                    <div style="font-size:13px;font-weight:700;color:#1e293b;" id="visLabel">🌐 Pública</div>
                    <div style="font-size:11px;color:#94a3b8;" id="visDesc">Visível para todos os usuários do sistema</div>
                </div>
            </label>
        </div>

    </form>

    <!-- Submit -->
    <div class="submit-section">
        <button type="button" class="submit-btn primary" id="btnSalvar">
            <i class="bi bi-check-circle"></i> Registrar Nota
        </button>
    </div>

</div>

<!-- Toast -->
<div class="toast-msg" id="toast"></div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
(function() {
    'use strict';

    const CNPJ = '<?= esc($cliente['cnpj']) ?>';
    const POST_URL = '<?= site_url('vendedor/nota') ?>';
    const DETAIL_URL = '<?= site_url('vendedor/cliente/' . $cliente['cnpj']) ?>';

    // Tipo selection
    document.querySelectorAll('.tipo-option').forEach(opt => {
        opt.addEventListener('click', () => {
            document.querySelectorAll('.tipo-option').forEach(o => o.classList.remove('selected'));
            opt.classList.add('selected');
        });
    });

    // Visibilidade toggle
    const chkPublica  = document.getElementById('chkPublica');
    const toggleTrack = document.getElementById('toggleTrack');
    const toggleThumb = document.getElementById('toggleThumb');
    const visLabel    = document.getElementById('visLabel');
    const visDesc     = document.getElementById('visDesc');
    const visWrap     = document.getElementById('visibilidadeToggleWrap');

    function atualizaToggle() {
        if (chkPublica.checked) {
            toggleTrack.style.background = '#22c55e';
            toggleThumb.style.left = '22px';
            visLabel.textContent = '🌐 Pública';
            visDesc.textContent  = 'Visível para todos os usuários do sistema';
            visWrap.style.borderColor = '#86efac';
        } else {
            toggleTrack.style.background = '#e5e7eb';
            toggleThumb.style.left = '2px';
            visLabel.textContent = '🔒 Privada';
            visDesc.textContent  = 'Somente você pode ver esta nota';
            visWrap.style.borderColor = '#e5e7eb';
        }
    }

    visWrap.addEventListener('click', () => {
        chkPublica.checked = !chkPublica.checked;
        atualizaToggle();
    });

    // Inicializa o estado visual do toggle ao carregar
    atualizaToggle();

    // Sentiment selection
    document.querySelectorAll('.sentiment-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.sentiment-btn').forEach(b => b.classList.remove('selected'));
            btn.classList.add('selected');
        });
    });

    // Char counter
    const textarea = document.getElementById('notaConteudo');
    const counter = document.getElementById('charCount');
    textarea.addEventListener('input', () => {
        counter.textContent = textarea.value.length;
    });

    // Submit — POST real
    const btnSalvar = document.getElementById('btnSalvar');
    btnSalvar.addEventListener('click', async () => {
        const tipo = document.querySelector('input[name="tipo"]:checked')?.value;
        const conteudo = textarea.value.trim();
        const sentimento = document.querySelector('input[name="sentimento"]:checked')?.value;

        if (!conteudo) {
            showToast('⚠️ Digite uma descrição antes de salvar.');
            textarea.focus();
            return;
        }

        btnSalvar.disabled = true;
        btnSalvar.innerHTML = '<span class="loading-spinner" style="width:18px;height:18px;border-width:2px;margin:0"></span> Salvando...';

        try {
            const publica = document.getElementById('chkPublica').checked ? '1' : '0';
            const body = new URLSearchParams({ cnpj: CNPJ, tipo, conteudo, sentimento, publica });
            const res = await fetch(POST_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
                body,
                credentials: 'same-origin',
            });
            const data = await res.json();

            if (data.success) {
                showToast('✅ ' + data.message);
                setTimeout(() => { window.location.href = DETAIL_URL; }, 1200);
            } else {
                showToast('❌ ' + (data.error || 'Erro ao salvar.'));
                btnSalvar.disabled = false;
                btnSalvar.innerHTML = '<i class="bi bi-check-circle"></i> Registrar Nota';
            }
        } catch (e) {
            showToast('❌ Erro de conexão.');
            btnSalvar.disabled = false;
            btnSalvar.innerHTML = '<i class="bi bi-check-circle"></i> Registrar Nota';
        }
    });

    function showToast(msg) {
        const toast = document.getElementById('toast');
        toast.textContent = msg;
        toast.classList.add('show');
        setTimeout(() => toast.classList.remove('show'), 3500);
    }
})();
</script>
<?= $this->endSection() ?>
