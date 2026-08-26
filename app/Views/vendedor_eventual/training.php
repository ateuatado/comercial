<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="container py-4" style="max-width: 900px">
    <a href="<?= site_url('vendedor-eventual') ?>" class="text-decoration-none">&larr; Voltar</a>
    <h1 class="h3 mt-3 mb-1"><?= esc($learning['title']) ?></h1>
    <p class="text-muted">Versão <?= esc($learning['version']) ?> · conteúdo demonstrativo</p>
    <?php if (session('error')): ?><div class="alert alert-danger"><?= esc(session('error')) ?></div><?php endif ?>

    <section class="card mb-4"><div class="card-header"><strong>1. Capacitação</strong></div><div class="card-body" style="white-space: pre-line"><?= esc($learning['training_content']) ?></div></section>

    <form method="post" action="<?= site_url('vendedor-eventual/campanhas/' . $campaignId . '/capacitacao') ?>">
        <?= csrf_field() ?>
        <section class="card mb-4"><div class="card-header"><strong>2. Avaliação</strong></div><div class="card-body"><p><?= esc($learning['assessment_question']) ?></p><?php foreach ($learning['assessment_options'] as $index => $option): ?><div class="form-check mb-2"><input class="form-check-input" type="radio" name="answer" id="answer-<?= $index ?>" value="<?= $index ?>" required><label class="form-check-label" for="answer-<?= $index ?>"><?= esc($option) ?></label></div><?php endforeach ?></div></section>
        <section class="card mb-4"><div class="card-header"><strong>3. Termos de participação</strong></div><div class="card-body"><div class="border rounded p-3 mb-3" style="white-space: pre-line; max-height: 320px; overflow:auto"><?= esc($learning['terms_content']) ?></div><div class="form-check"><input class="form-check-input" type="checkbox" name="accepted_terms" value="1" id="accepted-terms" required><label class="form-check-label" for="accepted-terms">Li e aceito expressamente os termos da versão <?= esc($learning['version']) ?>.</label></div></div></section>
        <button class="btn btn-success">Concluir avaliação e adesão</button>
    </form>
</div>
<?= $this->endSection() ?>
