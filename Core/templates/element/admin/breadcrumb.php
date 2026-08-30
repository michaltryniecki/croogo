<?php
/**
 * Breadcrumbs, rendered as the `.page-pretitle` line above the page title.
 *
 * @var \Croogo\Core\View\CroogoView $this
 */

if (empty($this->Breadcrumbs->getCrumbs())) {
    return;
}

$this->Breadcrumbs->setTemplates([
    'item' => '<li class="breadcrumb-item" {{attrs}}><a href="{{url}}"{{innerAttrs}}>{{title}}</a></li>{{separator}}',
    'itemWithoutLink' => '<li class="breadcrumb-item active" {{attrs}}><span{{innerAttrs}}>{{title}}</span></li>{{separator}}',
]);

$this->Breadcrumbs->prepend($this->Html->icon('home'), '/admin', ['escape' => false]);

echo $this->Html->tag(
    'nav',
    $this->Breadcrumbs->render(['class' => 'breadcrumb breadcrumb-arrows mb-0']),
    [
        // Deliberately NOT Tabler's `.page-pretitle`: that class uppercases its
        // content, which turns a trail of page names into shouting.
        'class' => 'd-none d-md-block mb-1',
        'aria-label' => __d('croogo', 'Breadcrumbs'),
    ]
);
