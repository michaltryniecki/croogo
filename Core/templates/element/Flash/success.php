<?php
/**
 * @var \Croogo\Core\View\CroogoView $this
 */

$escape = isset($params['escape']) ? $params['escape'] : true;

if ($escape) :
    $message = h($message);
endif;
?>
<div class="alert alert-success alert-dismissible" role="alert">
    <div class="d-flex">
        <div><?= $this->Html->icon('success-sign', ['class' => 'alert-icon']) ?></div>
        <div><?= $message ?></div>
    </div>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="<?= __d('croogo', 'Close') ?>"></button>
</div>
