<?php
/**
 * One dashboard widget.
 *
 * @var \Croogo\Core\View\CroogoView $this
 */

if (isset($dashboard['id'])) :
    $dataId = 'data-id="' . h($dashboard['id']) . '"';
else :
    $dataId = null;
endif;

?>
<div class="card card-<?= $alias ?> dashboard-card" id="<?= $alias ?>" <?= $dataId ?>>
    <div class="card-header">
        <?= $this->Html->icon('list', ['class' => 'move-handle me-2 text-secondary']) ?>
        <h3 class="card-title"><?= $dashboard['title'] ?></h3>
        <div class="card-actions">
            <a class="toggle-icon" data-bs-toggle="collapse" data-bs-target="#<?= $alias ?>-content"
               role="button" aria-expanded="<?= $dashboard['collapsed'] ? 'false' : 'true' ?>"
               aria-controls="<?= $alias ?>-content">
                <?= $dashboard['collapsed'] ? $this->Html->icon('plus') : $this->Html->icon('minus') ?>
            </a>
        </div>
    </div>
    <div class="card-body <?= $dashboard['collapsed'] ? 'collapse' : 'collapse show' ?>" id="<?= $alias ?>-content">
        <?php $cell = $this->cell(
            $dashboard['cell'],
            $dashboard['arguments'],
            ['cache' => $dashboard['cache'], 'alias' => $alias, 'dashboard' => $dashboard]
        ) ?>
        <?= $cell ?>
    </div>
</div>
