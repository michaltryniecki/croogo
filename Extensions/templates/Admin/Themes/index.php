<?php

$this->extend('Croogo/Core./Common/admin_index');

$this->assign('title', __d('croogo', 'Themes'));

$this->Breadcrumbs->add(
    __d('croogo', 'Extensions'),
    ['plugin' => 'Croogo/Extensions', 'controller' => 'Plugins', 'action' => 'index']
)
    ->add(__d('croogo', 'Themes'), $this->getRequest()->getUri()->getPath());

$this->start('action-buttons');
echo $this->Croogo->adminAction(__d('croogo', 'Upload'), ['action' => 'add']);
$this->end() ?>

<div class="extensions-themes row row-cards">
<?php
foreach ($themesData as $themeAlias => $theme) :
    // `row-cards` lays out COLUMNS; the element renders a bare card, so the
    // column is added here rather than inside it - the attachment chooser
    // reuses the same element at a different width.
    echo $this->Html->div(
        'col-md-6',
        $this->element('admin/theme-preview', ['theme' => $theme])
    );
endforeach;
?>
</div>
