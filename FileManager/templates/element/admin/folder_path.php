<?php
/**
 * Breadcrumbs of the current attachment folder, plus rename/delete for it.
 *
 * @var \Croogo\Core\View\CroogoView $this
 * @var array<\Croogo\FileManager\Model\Entity\AttachmentFolder> $folderPath
 * @var int|null $folderId
 * @var bool $allFolders
 * @var bool $manage Show rename/delete for the current folder
 */

$manage = $manage ?? false;
$baseQuery = array_diff_key(
    (array)$this->getRequest()->getQuery(),
    array_flip(['folder_id', 'page', 'sort', 'direction', 'search', 'all_folders'])
);
$crumbUrl = fn(?int $id) => [
    'plugin' => 'Croogo/FileManager',
    'controller' => 'Attachments',
    'action' => 'index',
    '?' => $baseQuery + ['folder_id' => $id ?? ''],
];

$items = [];
$items[] = $this->Html->link(
    $this->Html->icon('home', ['class' => 'me-1']) . __d('croogo', 'Root folder'),
    $crumbUrl(null),
    ['escape' => false]
);
foreach ($folderPath as $crumb) {
    $items[] = $this->Html->link($crumb->name, $crumbUrl($crumb->id));
}
$current = end($folderPath) ?: null;
?>
<div class="d-flex flex-wrap align-items-center gap-2 mb-3 folder-path">
    <ol class="breadcrumb m-0" aria-label="<?= h(__d('croogo', 'Folder')) ?>">
        <?php foreach ($items as $i => $item) : ?>
            <li class="breadcrumb-item<?= $i === count($items) - 1 ? ' active' : '' ?>"><?= $item ?></li>
        <?php endforeach ?>
    </ol>
    <?php if ($allFolders) : ?>
        <span class="badge bg-azure-lt"><?= __d('croogo', 'Searching in all folders') ?></span>
    <?php endif ?>
    <?php if ($manage && $current) : ?>
        <div class="btn-list ms-auto">
            <?= $this->Html->link(
                $this->Html->icon('pencil', ['class' => 'me-1']) . __d('croogo', 'Rename / move'),
                ['plugin' => 'Croogo/FileManager', 'controller' => 'AttachmentFolders', 'action' => 'edit', $current->id],
                ['escape' => false, 'class' => 'btn btn-sm btn-outline-secondary']
            ) ?>
            <?= $this->Form->postLink(
                $this->Html->icon('trash', ['class' => 'me-1']) . __d('croogo', 'Delete folder'),
                ['plugin' => 'Croogo/FileManager', 'controller' => 'AttachmentFolders', 'action' => 'delete', $current->id],
                [
                    'escapeTitle' => false,
                    'class' => 'btn btn-sm btn-outline-danger',
                    'confirm' => __d(
                        'croogo',
                        'Delete folder "%s"? Its files and subfolders will be moved to the parent folder; no files are deleted.',
                        $current->name
                    ),
                ]
            ) ?>
        </div>
    <?php endif ?>
</div>
