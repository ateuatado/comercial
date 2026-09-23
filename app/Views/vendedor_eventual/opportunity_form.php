<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<div class="container py-4">
  <div class="row justify-content-center">
    <div class="col-12 col-lg-7">
      <h1 class="h3">Registrar oportunidade</h1>
      <p class="text-muted">Registre apenas o mínimo necessário sobre o contato inicial.</p>
      <?php if (session('error')): ?>
        <div class="alert alert-danger"><?= esc(session('error')) ?></div>
      <?php endif ?>
      <div id="offline-status" class="alert alert-info d-none" role="status"></div>

      <!-- T012: Painel de consulta CNPJ -->
      <div class="card mb-3">
        <div class="card-header d-flex align-items-center gap-2">
          <span>Verificar CNPJ na base local</span>
          <small class="text-muted ms-auto">opcional — não bloqueia o registro</small>
        </div>
        <div class="card-body">
          <div class="input-group">
            <input id="cnpj-check-input" class="form-control" inputmode="numeric" maxlength="18" placeholder="00.000.000/0000-00">
            <button id="cnpj-check-btn" class="btn btn-outline-secondary" type="button">Consultar</button>
          </div>
          <div id="cnpj-result" class="mt-3" style="display:none"></div>
        </div>
      </div>

      <div class="card">
        <div class="card-body">
          <form id="opportunity-form" method="post" action="<?= site_url('vendedor-eventual/campanhas/' . $campaignId . '/oportunidades') ?>" class="row g-3" data-campaign-id="<?= esc((string) $campaignId) ?>" data-user-id="<?= esc((string) auth()->user()->id) ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="correlation_id" id="correlation-id">
            <input type="hidden" name="occurred_at" id="occurred-at">
            <input type="hidden" name="submission_mode" id="submission-mode" value="online">
            <div class="col-md-6">
              <label class="form-label">CNPJ</label>
              <input id="cnpj-field" class="form-control" name="cnpj" inputmode="numeric" maxlength="18" required value="<?= esc(old('cnpj')) ?>" placeholder="00.000.000/0000-00">
            </div>
            <div class="col-md-6">
              <label class="form-label">Canal do contato</label>
              <select class="form-select" name="channel" required>
                <?php foreach (['presencial' => 'Presencial', 'telefone' => 'Telefone', 'email' => 'E-mail', 'evento' => 'Evento', 'outro' => 'Outro'] as $value => $label): ?>
                  <option value="<?= $value ?>"><?= $label ?></option>
                <?php endforeach ?>
              </select>
            </div>
            <div class="col-12">
              <label class="form-label">Contexto do contato</label>
              <textarea class="form-control" name="contact_context" rows="4" maxlength="1000" required placeholder="Descreva brevemente como surgiu a necessidade."><?= esc(old('contact_context')) ?></textarea>
            </div>
            <div class="col-12">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" value="1" name="cnpj_confirmed" id="cnpj-confirmed" required <?= old('cnpj_confirmed') === '1' ? 'checked' : '' ?>>
                <label class="form-check-label" for="cnpj-confirmed">Confirmo que o CNPJ e os dados sugeridos foram conferidos com o cliente.</label>
              </div>
              <div class="form-text">A confirmação ficará registrada na linha do tempo da oportunidade.</div>
            </div>
            <div class="col-12 d-flex gap-2">
              <button class="btn btn-primary" id="opportunity-submit">Registrar oportunidade</button>
              <a class="btn btn-outline-secondary" href="<?= site_url('vendedor-eventual') ?>">Cancelar</a>
            </div>
            <div class="col-12">
              <small class="text-muted">Sem conexão, o registro fica no armazenamento local deste navegador por até 24 horas e é apagado após a sincronização.</small>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
(function () {
  const btn   = document.getElementById('cnpj-check-btn');
  const input = document.getElementById('cnpj-check-input');
  const box   = document.getElementById('cnpj-result');
  const field = document.getElementById('cnpj-field');
  const escapeHtml = (value) => String(value ?? '').replace(/[&<>'"]/g, (char) => ({
    '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;'
  })[char]);

  btn.addEventListener('click', async () => {
    const raw = input.value.replace(/\D/g, '');
    if (raw.length !== 14) {
      box.style.display = 'block';
      box.innerHTML = '<div class="alert alert-warning mb-0">Informe um CNPJ com 14 dígitos.</div>';
      return;
    }
    btn.disabled = true;
    btn.textContent = 'Consultando…';
    try {
      const res  = await fetch(`<?= site_url('vendedor-eventual/cnpj/') ?>${raw}`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
      const data = await res.json();
      box.style.display = 'block';
      if (data.found) {
        const d = data.data;
        box.innerHTML = `
          <div class="alert alert-success mb-0">
            <strong>CNPJ encontrado</strong> <small class="text-muted">(fonte: ${escapeHtml(data.source)})</small><br>
            ${d.razao_social  ? `<strong>Razão social:</strong> ${escapeHtml(d.razao_social)}<br>` : ''}
            ${d.nome_fantasia ? `<strong>Nome fantasia:</strong> ${escapeHtml(d.nome_fantasia)}<br>` : ''}
            ${d.situacao      ? `<strong>Situação:</strong> ${escapeHtml(d.situacao)}<br>` : ''}
            ${d.cnae_principal? `<strong>CNAE principal:</strong> ${escapeHtml(d.cnae_principal)}<br>` : ''}
            ${(d.municipio && d.uf) ? `<strong>Localidade:</strong> ${escapeHtml(d.municipio)} / ${escapeHtml(d.uf)}<br>` : ''}
            <button class="btn btn-sm btn-outline-primary mt-2" id="fill-cnpj-btn" type="button">Usar este CNPJ no formulário</button>
          </div>`;
        document.getElementById('fill-cnpj-btn').addEventListener('click', () => {
          field.value = input.value;
        });
      } else {
        box.innerHTML = `<div class="alert alert-secondary mb-0">CNPJ não encontrado na base local. Você ainda pode registrar a oportunidade normalmente.</div>`;
      }
    } catch (e) {
      box.innerHTML = `<div class="alert alert-danger mb-0">Erro ao consultar. Tente novamente ou prossiga sem a consulta.</div>`;
    } finally {
      btn.disabled = false;
      btn.textContent = 'Consultar';
    }
  });
})();
</script>
<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script src="<?= base_url('assets/js/vendedor-eventual-offline.js') ?>"></script>
<?= $this->endSection() ?>
