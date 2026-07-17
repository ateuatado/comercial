<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="container-fluid py-4" style="max-width: 800px;">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1"><i class="bi bi-pencil-square"></i> Editar Mensagem</h4>
            <p class="text-muted small mb-0">
                Slug: <code><?= esc($mensagem['slug']) ?></code>
            </p>
        </div>
        <a href="<?= site_url('admin/mensagens') ?>" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Voltar
        </a>
    </div>

    <form action="<?= site_url('admin/mensagens/' . $mensagem['slug']) ?>" method="post">
        <?= csrf_field() ?>

        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body">
                <div class="mb-3">
                    <label for="titulo" class="form-label fw-bold">Título</label>
                    <input type="text" class="form-control" id="titulo" name="titulo"
                           value="<?= esc($mensagem['titulo']) ?>" required>
                </div>

                <div class="mb-3">
                    <label for="conteudo" class="form-label fw-bold">Conteúdo</label>
                    <textarea class="form-control" id="conteudo" name="conteudo"
                              rows="12"
                    ><?= esc($mensagem['conteudo']) ?></textarea>
                </div>

                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" id="ativo" name="ativo" value="1"
                           <?= $mensagem['ativo'] ? 'checked' : '' ?>>
                    <label class="form-check-label" for="ativo">Mensagem ativa</label>
                </div>
            </div>
        </div>

        <!-- Preview -->
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-light">
                <i class="bi bi-eye"></i> Pré-visualização
            </div>
            <div class="card-body" id="previewArea">
                <?= $mensagem['conteudo'] ?>
            </div>
        </div>

        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-check-lg"></i> Salvar
            </button>
            <a href="<?= site_url('admin/mensagens') ?>" class="btn btn-outline-secondary">Cancelar</a>
        </div>
    </form>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= base_url('assets/js/tinymce/tinymce.min.js') ?>"></script>
<script>
tinymce.init({
    selector: '#conteudo',
    height: 350,
    menubar: false,
    language: 'pt_BR',
    plugins: 'lists link code preview',
    toolbar: 'undo redo | bold italic underline | bullist numlist | link | code preview',
    content_style: 'body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; font-size: 14px; }',
    setup: function(editor) {
        editor.on('change keyup', function() {
            editor.save();
            document.getElementById('previewArea').innerHTML = editor.getContent();
        });
    }
});
</script>
<?= $this->endSection() ?>
