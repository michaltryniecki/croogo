<?php
$folderBrowsing = !empty($folderBrowsing);
if ($folderBrowsing) :
    ?>
<div class="row attachments-chooser-with-folders">
    <div class="col-md-3">
        <?= $this->element('Croogo/FileManager.admin/folder_tree') ?>
    </div>
    <div class="col-md-9">
        <?= $this->element('Croogo/FileManager.admin/folder_path') ?>
    <?php
endif;
?>
<div class="<?php echo $this->Theme->getCssClass('row'); ?>">
    <div class="<?php echo $this->Theme->getCssClass('columnFull'); ?>">
    <?php
        echo __d('croogo', 'Sort by:');
        echo ' ' . $this->Paginator->sort('id', __d('croogo', 'Id'), ['class' => 'sort']);
        echo ', ' . $this->Paginator->sort('title', __d('croogo', 'Title'), ['class' => 'sort']);
        echo ', ' . $this->Paginator->sort('created', __d('croogo', 'Created'), ['class' => 'sort']);
    ?>
    </div>
</div>

<div class="<?php echo $this->Theme->getCssClass('row'); ?>">
    <div class="<?php echo $this->Theme->getCssClass('columnFull'); ?>">
        <?php //echo $this->element('FileManager.admin/attachments_search'); ?>
        <hr />
    </div>
</div>
<div class="<?php echo $this->Theme->getCssClass('row'); ?>">
    <div class="<?php echo $this->Theme->getCssClass('columnFull'); ?>">
        <div id="attachments-for-links" class="row row-cards">
        <?php foreach ($attachments as $attachment) : ?>
            <div class="card">
                <?php
                if (preg_match('/^image/', $attachment->asset->mime_type)) :
                    echo $this->Html->image($attachment->asset->path, [
                        'class' => 'card-img-top',
                    ]);
                endif;
                ?>

                <div class="card-body">
                <?php

                echo $this->Html->para(
                    null,
                    $this->Html->link(
                        $attachment->asset->filename,
                        $attachment->asset->path,
                        [
                            'class' => 'item-choose',
                            'data-chooser_type' => 'Attachment',
                            'data-chooser_id' => $attachment->asset->id,
                            'data-chooser_title' => $attachment->asset->filename,
                            'rel' => $attachment->asset->path,
                        ]
                    )
                );

                echo $this->Html->para(
                    null,
                    __d('croogo', 'Created') . ': ' .
                    $this->Time->nice($attachment->asset->created)
                );
                ?>
                </div>
            </div>
        <?php endforeach; ?>
        </div>
        <?php if ($attachments->count() === 0 && $folderBrowsing) : ?>
            <div class="empty chooser-empty">
                <p class="empty-title"><?= __d('croogo', 'No files in this folder') ?></p>
                <div class="empty-action">
                    <?= $this->Html->link(
                        __d('croogo', 'Upload files'),
                        [
                            'action' => 'add',
                            '?' => $folderId !== null ? ['folder_id' => $folderId] : [],
                        ],
                        ['class' => 'btn btn-primary']
                    ) ?>
                </div>
            </div>
        <?php endif ?>
        <?php echo $this->element('admin/pagination', ['paginationClass' => 'mt-3']); ?>
    </div>
</div>
<?php if ($folderBrowsing) : ?>
    </div>
</div>
<script>
// Reopen the chooser on the folder it was last left in. Only when the URL
// names no folder at all: the tree's root link sends an explicit empty
// folder_id, so picking the root is remembered too instead of bouncing back.
(function () {
    var key = 'croogo.attachments.chooser.folder';
    try {
        var params = new URLSearchParams(window.location.search);
        if (!params.has('folder_id')) {
            var last = window.localStorage.getItem(key);
            if (last) {
                params.set('folder_id', last);
                window.location.replace(window.location.pathname + '?' + params.toString());
            }
            return;
        }
        window.localStorage.setItem(key, <?= json_encode((string)($folderId ?? '')) ?>);
    } catch (e) {
        // Storage blocked (private window, policy): the chooser simply starts at the root.
    }
})();
</script>
<?php endif ?>
