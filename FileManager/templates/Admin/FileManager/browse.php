<?php

$this->extend('Croogo/Core./Common/admin_index');
$tableHeaderClass = $this->Theme->getCssClass('tableHeaderClass');
// This template renders its own body, so admin_index leaves the framing to it
// (see the `content` note there) - hence the explicit card below.
$tableClass = $this->Theme->getCssClass('tableClass');

$this->assign('title', __d('croogo', 'File Manager'));
$this->Breadcrumbs->add(__d('croogo', 'File Manager'), $this->getRequest()->getRequestTarget());

?>

<?php $this->start('action-buttons') ?>
<div class="btn-group">
    <?php
    echo $this->FileManager->adminAction(
        __d('croogo', 'Upload here'),
        ['controller' => 'FileManager', 'action' => 'upload'],
        $path
    );
    echo $this->FileManager->adminAction(
        __d('croogo', 'Create directory'),
        ['controller' => 'FileManager', 'action' => 'create_directory'],
        $path
    );
    echo $this->FileManager->adminAction(
        __d('croogo', 'Create file'),
        ['controller' => 'FileManager', 'action' => 'create_file'],
        $path
    );
    ?>
</div>
<?php $this->end() ?>

<div class="card">
    <div class="card-header">
        <?= $this->element('Croogo/FileManager.admin/breadcrumbs') ?>
    </div>
    <div class="directory-content table-responsive">
    <table class="<?= $tableClass ?>">
        <?php
        $tableHeaders = $this->Html->tableHeaders([
            '',
            __d('croogo', 'Directory content'),
            __d('croogo', 'Actions'),
        ]);
        ?>
        <thead class="<?= $tableHeaderClass ?>">
            <?= $tableHeaders ?>
        </thead>
        <?php
        // directories
        $rows = [];
        foreach ($content['0'] as $directory) :
            $actions = [];
            $fullpath = $path . $directory;
            $actions[] = $this->FileManager->linkDirectory(__d('croogo', 'Open'), $fullpath . DS);
            if ($this->FileManager->isDeletable($fullpath)) {
                $actions[] = $this->FileManager->link(__d('croogo', 'Delete'), [
                    'controller' => 'FileManager',
                    'action' => 'delete_directory',
                ], $fullpath);
            }
            $actions[] = $this->FileManager->link(__d('croogo', 'Rename'), [
                'controller' => 'FileManager',
                'action' => 'rename',
            ], $fullpath);
            $actions = $this->Html->div('item-actions', implode(' ', $actions));
            $rows[] = [
                $this->Html->icon('folder', ['class' => 'text-yellow fs-3']),
                $this->FileManager->linkDirectory($directory, $fullpath . DS),
                $actions,
            ];
        endforeach;
        echo $this->Html->tableCells($rows, ['class' => 'directory-listing'], ['class' => 'directory-listing']);

        // files
        $rows = [];
        foreach ($content['1'] as $file) :
            $actions = [];
            $fullpath = $path . $file;
            $icon = $this->FileManager->filename2icon($file);
            if ($icon === 'photo') :
                $image = '/' . str_replace(WWW_ROOT, '', $fullpath);
                $lightboxOptions = [
                    'data-toggle' => 'lightbox',
                    'escape' => false,
                ];
                $linkFile = $this->Html->link($file, $image, $lightboxOptions);
                $actions[] = $this->Html->link(__d('croogo', 'View'), $image, $lightboxOptions);
            else :
                $linkFile = $this->FileManager->linkFile($file, $fullpath);
                $actions[] = $this->FileManager->link(__d('croogo', 'Edit'), [
                        'plugin' => 'Croogo/FileManager',
                        'controller' => 'FileManager',
                        'action' => 'edit_file',
                    ], $fullpath);
            endif;
            if ($this->FileManager->isDeletable($fullpath)) {
                $actions[] = $this->FileManager->link(__d('croogo', 'Delete'), [
                    'plugin' => 'Croogo/FileManager',
                    'controller' => 'FileManager',
                    'action' => 'delete_file',
                ], $fullpath);
            }
            $actions[] = $this->FileManager->link(__d('croogo', 'Rename'), [
                'plugin' => 'Croogo/FileManager',
                'controller' => 'FileManager',
                'action' => 'rename',
            ], $fullpath);
            $actions = $this->Html->div('item-actions', implode(' ', $actions));
            $rows[] = [
                $this->Html->icon($icon, ['class' => 'text-secondary fs-3']),
                $linkFile,
                $actions,
            ];
        endforeach;
        echo $this->Html->tableCells($rows, ['class' => 'file-listing'], ['class' => 'file-listing']);

        ?>
    </table>
    </div>
</div>
