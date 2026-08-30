<?php
/**
 * Pagination, styled as a Tabler card footer.
 *
 * The `.page-item`/`.page-link` markup Bootstrap needs comes from
 * Croogo\Core\View\Helper\PaginatorHelper's own templates, so this element only
 * decides the layout and which controls to show.
 *
 * @var \Croogo\Core\View\CroogoView $this
 */

$chevronLeft = $this->Html->icon('chevron-left', ['class' => 'me-1']);
$chevronRight = $this->Html->icon('chevron-right', ['class' => 'ms-1']);

// Index pages put the pager in the footer of the card holding the table; the
// attachment chooser renders it loose inside a modal, and overrides this.
$paginationClass = isset($paginationClass) ? $paginationClass : 'card-footer';
?>
<div class="<?= $paginationClass ?> d-flex align-items-center flex-wrap gap-2">
    <p class="m-0 text-secondary">
        <?php
        // Cake 5: counter() przyjmuje string (w Cake 3 tablicę z 'format'). Tokeny
        // {{count}}/{{current}} Cake mapuje po staremu (count -> totalCount).
        echo $this->Paginator->counter(__d(
            'croogo',
            'Page {{page}} of {{pages}}, showing {{current}} records out of {{count}} total'
        ));
        ?>
    </p>
    <ul class="pagination m-0 ms-auto">
        <?= $this->Paginator->prev($chevronLeft . __d('croogo', 'prev'), ['escape' => false]) ?>
        <?= $this->Paginator->numbers() ?>
        <?= $this->Paginator->next(__d('croogo', 'next') . $chevronRight, ['escape' => false]) ?>
    </ul>
</div>
