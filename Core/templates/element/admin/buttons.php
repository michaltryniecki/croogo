<?php
/**
 * Save / Apply / Cancel for admin forms.
 *
 * @var \Croogo\Core\View\CroogoView $this
 */

$cancelUrl = isset($cancelUrl) ? $cancelUrl : ['action' => 'index'];
$saveText = isset($saveText) ? $saveText : __d('croogo', 'Save');
$applyText = isset($applyText) ? $applyText : __d('croogo', 'Apply');
$cancelText = isset($cancelText) ? $cancelText : __d('croogo', 'Cancel');

$saveLabel = $this->Html->icon('save', ['class' => 'me-1']) . $saveText;
$applyLabel = $this->Html->icon('bolt', ['class' => 'me-1']) . $applyText;
$cancelLabel = $this->Html->icon('times', ['class' => 'me-1']) . $cancelText;

?>
<div class="btn-list justify-content-end admin-form-actions">
    <?php
    // Outlined danger: leaving the form discards whatever was typed, so it
    // reads as the destructive option without competing with Save for weight.
    echo $this->Html->link($cancelLabel, $cancelUrl, [
        'escapeTitle' => false,
        'class' => 'cancel btn btn-outline-danger',
    ]);

    if ($applyText) :
        // Cake 5: Form->button escapuje tytul przez escapeTitle (nie escape). Etykieta
        // jest HTML (ikona), wiec bez tego renderowaloby doslownie <i ...>Zastosuj.
        echo $this->Form->button($applyLabel, [
            'class' => 'btn btn-outline-primary',
            'name' => '_apply',
            'escapeTitle' => false,
        ]);
    endif;

    echo $this->Form->button($saveLabel, [
        'class' => 'btn btn-primary',
        'escapeTitle' => false,
    ]);
    ?>
</div>
