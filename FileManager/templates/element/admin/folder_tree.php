<?php
/**
 * Folder tree for the attachment library and the file chooser.
 *
 * Folder links keep the rest of the query string (chooser, chooser_type,
 * links...) so navigating inside the chooser popup stays a chooser, and drop
 * what belongs to the previous folder's listing (page, sort, search).
 *
 * @var \Croogo\Core\View\CroogoView $this
 * @var array<\Croogo\FileManager\Model\Entity\AttachmentFolder> $folderTree
 * @var int $rootFolderCount
 * @var int|null $folderId
 * @var bool $allFolders
 * @var bool $manage Show the "new folder" button
 */

$manage = $manage ?? false;
$baseQuery = array_diff_key(
    (array)$this->getRequest()->getQuery(),
    array_flip(['folder_id', 'page', 'sort', 'direction', 'search', 'all_folders'])
);

$folderUrl = function (?int $id) use ($baseQuery) {
    $query = $baseQuery;
    // The root gets an explicit empty value, so the chooser can tell "the user
    // opened the root" from "no folder given" (see chooser.php).
    $query['folder_id'] = $id ?? '';

    return [
        'plugin' => 'Croogo/FileManager',
        'controller' => 'Attachments',
        'action' => 'index',
        '?' => $query,
    ];
};

$renderItem = function ($label, ?int $id, int $count) use ($folderUrl, $folderId, $allFolders) {
    $active = !$allFolders && $id === $folderId;
    $icon = $id === null ? 'home' : ($active ? 'folder-open' : 'folder');

    return $this->Html->link(
        $this->Html->icon($icon, ['class' => 'me-1 ' . ($id === null ? '' : 'text-yellow')]) .
        $this->Html->tag('span', h($label), ['class' => 'folder-tree-name']) .
        $this->Html->tag('span', (string)$count, ['class' => 'badge bg-secondary-lt ms-auto']),
        $folderUrl($id),
        [
            'escape' => false,
            'class' => 'folder-tree-link d-flex align-items-center' . ($active ? ' active' : ''),
            'data-folder-id' => $id ?? '',
            'aria-current' => $active ? 'true' : null,
        ]
    );
};

$renderBranch = function (array $folders) use (&$renderBranch, $renderItem) {
    if (!$folders) {
        return '';
    }
    $out = '<ul class="folder-tree-branch">';
    foreach ($folders as $folder) {
        $children = $folder->children ?? [];
        $out .= '<li>' . $renderItem($folder->name, $folder->id, (int)$folder->attachment_count);
        $out .= $renderBranch($children);
        $out .= '</li>';
    }

    return $out . '</ul>';
};
?>
<div class="card folder-tree mb-3">
    <div class="card-header py-2 d-flex align-items-center">
        <h3 class="card-title m-0"><?= __d('croogo', 'Folders') ?></h3>
        <?php if ($manage) : ?>
            <?= $this->Html->link(
                $this->Html->icon('folder-plus'),
                [
                    'plugin' => 'Croogo/FileManager',
                    'controller' => 'AttachmentFolders',
                    'action' => 'add',
                    '?' => $folderId !== null ? ['parent_id' => $folderId] : [],
                ],
                [
                    'escape' => false,
                    'class' => 'btn btn-sm btn-outline-primary ms-auto',
                    'title' => __d('croogo', 'New folder here'),
                ]
            ) ?>
        <?php endif ?>
    </div>
    <div class="card-body p-2">
        <ul class="folder-tree-branch folder-tree-root">
            <li>
                <?= $renderItem(__d('croogo', 'Root folder'), null, (int)$rootFolderCount) ?>
                <?= $renderBranch($folderTree) ?>
            </li>
        </ul>
    </div>
</div>
<style>
    .folder-tree-branch { list-style: none; margin: 0; padding-left: 0; }
    .folder-tree-branch .folder-tree-branch { padding-left: 1rem; }
    .folder-tree-link {
        gap: .25rem;
        padding: .25rem .5rem;
        border-radius: var(--tblr-border-radius, 4px);
        color: inherit;
        text-decoration: none;
    }
    .folder-tree-link:hover { background: var(--tblr-bg-surface-secondary, rgba(0,0,0,.04)); text-decoration: none; }
    .folder-tree-link.active { background: var(--tblr-primary-lt, rgba(32,107,196,.1)); font-weight: 600; }
    .folder-tree-name { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
</style>
