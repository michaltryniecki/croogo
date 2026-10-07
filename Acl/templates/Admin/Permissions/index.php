<?php

$tabContentClass = $this->Theme->getCssClass('tabContentClass');

$this->extend('Croogo/Core./Common/admin_index');

$this->Croogo->adminScript('Croogo/Acl.acl_permissions');

$this->Breadcrumbs->add(
    __d('croogo', 'Users'),
    ['plugin' => 'Croogo/Users', 'controller' => 'Users', 'action' => 'index']
)
    ->add(__d('croogo', 'Permissions'), $this->getRequest()->getUri()->getPath());

$this->append('action-buttons');
$toolsButton = $this->Html->link(__d('croogo', 'Tools'), '#', [
        'button' => 'outline-secondary btn-sm',
        'class' => 'dropdown-toggle',
        'data-toggle' => 'dropdown',
        'escape' => false,
    ]);

// Cake 5 drops URL-array keys that are not route elements: a query string goes under '?',
// or Generate lands on the Actions screen instead of returning here.
// No Synchronize entry: its `sync` key was dropped the same way, so it only ever generated,
// and a working one would delete ACO nodes (with their grants) that this app does not load
// from the database it shares with the legacy app.
$generateUrl = [
    'plugin' => 'Croogo/Acl',
    'controller' => 'Actions',
    'action' => 'generate',
    '?' => ['permissions' => 1],
];
$out = $this->Croogo->adminAction(__d('croogo', 'Generate'), $generateUrl, [
        'button' => false,
        'list' => true,
        'method' => 'post',
        'class' => 'dropdown-item',
        'tooltip' => [
            'data-title' => __d('croogo', 'Create new actions (no removal)'),
            'data-placement' => 'left',
        ],
    ]);
echo $this->Html->div('btn-group', $toolsButton . $this->Html->tag('ul', $out, [
    'class' => 'dropdown-menu dropdown-menu-right',
]));

echo $this->Croogo->adminAction(
    __d('croogo', 'Edit Actions'),
    ['controller' => 'Actions', 'action' => 'index', 'permissions' => 1]
);
$this->end();

$this->Js->buffer('AclPermissions.tabSwitcher();');

?>
<div class="<?= $this->Theme->getCssClass('row') ?>">
    <div class="<?= $this->Theme->getCssClass('columnFull') ?>">

        <ul id="permissions-tab" class="nav nav-tabs">
        <?php
            echo $this->Croogo->adminTabs();
        ?>
        </ul>

        <div class="<?= $tabContentClass ?>">
            <?= $this->Croogo->adminTabs() ?>
        </div>

    </div>
</div>
