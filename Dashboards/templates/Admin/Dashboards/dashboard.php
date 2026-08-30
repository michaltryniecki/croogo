<?php
/**
 * The admin landing page.
 *
 * @var \Croogo\Core\View\CroogoView $this
 */

$this->assign('title', __d('croogo', 'Dashboards'));

$this->Croogo->adminScript('Croogo/Dashboards.admin');
$this->Html->css('Croogo/Dashboards.admin', ['block' => true]);

$this->Breadcrumbs->add(__d('croogo', 'Dashboard'), $this->getRequest()->getRequestTarget());

$dashboards = $this->Dashboards->dashboards();

// A base install has no plugin registering a dashboard widget, so the helper
// returns nothing but its empty sortable columns - which renders as a blank
// page. Tabler's empty state says so instead.
if (strpos($dashboards, 'dashboard-card') === false) :
    ?>
    <div class="card">
        <div class="empty">
            <div class="empty-icon">
                <?= $this->Html->icon('list') ?>
            </div>
            <p class="empty-title"><?= __d('croogo', 'Nothing on your dashboard yet') ?></p>
            <p class="empty-subtitle text-secondary">
                <?= __d('croogo', 'Dashboard widgets are contributed by plugins. Enable a plugin that provides one, and it will show up here.') ?>
            </p>
            <div class="empty-action">
                <?= $this->Html->link(
                    $this->Html->icon('magic', ['class' => 'me-1']) . __d('croogo', 'Manage plugins'),
                    ['plugin' => 'Croogo/Extensions', 'controller' => 'Plugins', 'action' => 'index'],
                    ['class' => 'btn btn-primary', 'escapeTitle' => false]
                ) ?>
            </div>
        </div>
    </div>
    <?php
    return;
endif;

echo $dashboards;

$this->Js->buffer('Dashboard.init();');
?>
<div id="dashboard-url" style="display: none"><?= $this->Url->build(['action' => 'save']) ?></div>
