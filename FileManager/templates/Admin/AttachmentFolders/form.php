<?php
/**
 * Create / rename / move an attachment folder.
 *
 * @var \Croogo\Core\View\CroogoView $this
 * @var \Croogo\FileManager\Model\Entity\AttachmentFolder $folder
 * @var array $parentOptions
 * @var array $path Folders from the root down to the current one
 */

$this->extend('Croogo/Core./Common/admin_edit');

$attachmentsUrl = [
    'plugin' => 'Croogo/FileManager',
    'controller' => 'Attachments',
    'action' => 'index',
];

$this->Breadcrumbs->add(__d('croogo', 'Attachments'), $attachmentsUrl);
foreach ($path as $crumb) :
    $this->Breadcrumbs->add(h($crumb->name), $attachmentsUrl + ['?' => ['folder_id' => $crumb->id]]);
endforeach;

$isNew = $folder->isNew();
$this->assign('title', $isNew ? __d('croogo', 'New Folder') : __d('croogo', 'Edit Folder'));
if ($isNew) :
    $this->Breadcrumbs->add(__d('croogo', 'New Folder'), $this->getRequest()->getRequestTarget());
endif;

$this->append('form-start', $this->Form->create($folder));

$this->append('tab-heading');
echo $this->Croogo->adminTab(__d('croogo', 'Folder'), '#folder-main');
$this->end();

$this->append('tab-content');
echo $this->Html->tabStart('folder-main');
echo $this->Form->input('name', [
    'label' => __d('croogo', 'Name'),
    'autofocus' => true,
]);
echo $this->Form->input('parent_id', [
    'label' => __d('croogo', 'Parent folder'),
    'type' => 'select',
    'options' => $parentOptions,
    'empty' => __d('croogo', '(root)'),
    'help' => $isNew ? null : __d('croogo', 'Moving a folder moves its files and subfolders with it. File URLs do not change.'),
]);
echo $this->Html->tabEnd();
$this->end();

$cancelUrl = $attachmentsUrl;
$cancelFolder = $isNew ? $folder->parent_id : $folder->id;
if ($cancelFolder) :
    $cancelUrl['?'] = ['folder_id' => $cancelFolder];
endif;

$this->start('panels');
echo $this->Html->beginBox(__d('croogo', 'Publishing'));
echo $this->element('Croogo/Core.admin/buttons', [
    'cancelUrl' => $cancelUrl,
    'applyText' => false,
]);
echo $this->Html->endBox();
$this->end();
