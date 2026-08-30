<?php
/**
 * Shared skeleton for admin view pages.
 *
 * @var \Croogo\Core\View\CroogoView $this
 */

use Cake\Utility\Inflector;
use Cake\Utility\Text;

if (empty($modelClass)) {
    $modelClass = Inflector::singularize($this->name);
}
if (!isset($className)) {
    $className = strtolower($this->name);
}

$humanName = Inflector::humanize(Inflector::underscore($modelClass));
$i18nDomain = $this->getRequest()->getParam('plugin') ?: 'croogo';

$rowClass = $this->Theme->getCssClass('row');
$columnFull = $this->Theme->getCssClass('columnFull');
$tableClass = isset($tableClass) ? $tableClass : $this->Theme->getCssClass('tableClass');
$tabContentClass = $this->Theme->getCssClass('tabContentClass');

// Matches the id admin_edit.php builds, and is what the default tab links to.
// It used to be read without ever being assigned, so the single tab pointed at
// `#` and the pane it was supposed to reveal had no id.
$tabId = 'tabitem-' . Text::slug(strtolower($modelClass), '-');

$showActions = isset($showActions) ? $showActions : true;

if ($pageHeading = trim($this->fetch('page-heading'))) :
    echo $pageHeading;
endif;

if (empty($this->fetch('action-buttons'))) {
    $entityName = __d($i18nDomain, $humanName);
    $this->assign('action-buttons', $this->Croogo->adminAction(
        __d('croogo', 'New %s', $entityName),
        ['action' => 'add'],
        ['button' => 'primary', 'icon' => 'create']
    ));
}
?>

    <div class="<?= $rowClass ?>">
        <div class="<?= $columnFull ?>">
            <?php
            if ($contentBlock = trim($this->fetch('content'))) :
                echo $this->Html->div('card', $this->Html->div('card-body', $contentBlock));
            elseif ($mainBlock = trim($this->fetch('main'))) :
                echo $mainBlock;
            else :
                if ($tabHeading = $this->fetch('tab-heading')) :
                    $tabs = $tabHeading;
                else :
                    $tabs = $this->Croogo->adminTab(__d('croogo', $modelClass), "#$tabId");
                endif;
                $tabs .= $this->Croogo->adminTabs();

                $tabContent = trim($this->fetch('tab-content'));
                if (!$tabContent) :
                    $content = '';
                    foreach ($editFields as $field => $opts) :
                        if (is_string($opts)) {
                            $field = $opts;
                            $opts = [
                                'label' => false,
                                'tooltip' => ucfirst($field),
                            ];
                        }
                        $content .= $this->Form->input($field, $opts);
                    endforeach;

                    if (!empty($content)) :
                        $tabContent = $this->Html->div('tab-pane', $content, ['id' => $tabId]);
                    endif;
                endif;
                $tabContent .= $this->Croogo->adminTabs();
                ?>
                <div class="card">
                    <div class="card-header">
                        <?= $this->Html->tag('ul', $tabs, [
                            'class' => 'nav nav-tabs card-header-tabs',
                            'data-bs-toggle' => 'tabs',
                            'role' => 'tablist',
                        ]) ?>
                    </div>
                    <div class="card-body">
                        <?= $this->Html->div($tabContentClass, $tabContent) ?>
                    </div>
                </div>
                <?php
            endif;
            ?>
        </div>
    </div>

<?php

if ($pageFooter = trim($this->fetch('page-footer'))) :
    echo $pageFooter;
endif;
