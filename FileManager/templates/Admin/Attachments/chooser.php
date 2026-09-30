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
<?php
/*
 * The grid.
 *
 * Each card used to be a full-width block holding the ORIGINAL file: a page of camera
 * photos was tens of megabytes to download and one photo per screen to scroll through.
 * Now a card is a grid cell with a thumbnail - the same cached, resized version the
 * library list shows - and the original is only what the link points at.
 *
 * What callers rely on is unchanged: every selectable file is an `a.item-choose` whose
 * href is the asset path, inside a `.card`.
 */
$isNew = function ($created): bool {
    return $created instanceof DateTimeInterface && $created->getTimestamp() > time() - 900;
};
$pages = (int)$this->Paginator->total();
?>
<?php if ($pages > 1) : ?>
    <?php echo $this->element('admin/pagination', ['paginationClass' => 'mb-3']); ?>
<?php endif ?>
<div class="<?php echo $this->Theme->getCssClass('row'); ?>">
    <div class="<?php echo $this->Theme->getCssClass('columnFull'); ?>">
        <div id="attachments-for-links" class="row row-cards">
        <?php foreach ($attachments as $attachment) : ?>
            <?php
            $asset = $attachment->asset;
            $isImage = (bool)preg_match('/^image/', (string)$asset->mime_type);
            $thumbnail = null;
            if ($isImage) {
                $thumbnail = $this->AssetsImage->resize($asset->path, 200, 200, [
                    'adapter' => $asset->adapter,
                ], [
                    'alt' => $asset->filename,
                    'class' => 'chooser-thumb-img',
                    'loading' => 'lazy',
                ]);
            }
            // An image whose file is gone from disk cannot be chosen: it would put a
            // broken picture wherever it was inserted.
            $missing = $isImage && !is_string($thumbnail);
            ?>
            <div class="col-6 col-sm-4 col-lg-3">
                <div class="card card-sm h-100<?= $missing ? ' chooser-missing' : '' ?>">
                    <div class="chooser-thumb">
                        <?php
                        if ($missing) :
                            echo $this->AssetsImage->missing((string)$asset->path);
                        elseif ($isImage) :
                            echo $thumbnail;
                        else :
                            echo $this->Html->image('Croogo/Core./img/icons/page_white.png', [
                                'alt' => (string)$asset->mime_type,
                            ]);
                        endif;
                        ?>
                    </div>
                    <div class="card-body p-2">
                        <?php
                        if ($missing) :
                            echo $this->Html->div('text-truncate text-secondary', h($asset->filename), [
                                'title' => $asset->filename,
                            ]);
                        else :
                            echo $this->Html->link(
                                $asset->filename,
                                $asset->path,
                                [
                                    'class' => 'item-choose d-block text-truncate',
                                    'title' => $asset->filename,
                                    'data-chooser_type' => 'Attachment',
                                    'data-chooser_id' => $asset->id,
                                    'data-chooser_title' => $asset->filename,
                                    'rel' => $asset->path,
                                ],
                            );
                        endif;
                        ?>
                        <div class="text-secondary small">
                            <?= h($this->Time->nice($asset->created)) ?>
                            <?php if ($isNew($asset->created)) : ?>
                                <span class="badge bg-green-lt ms-1"><?= __d('croogo', 'New') ?></span>
                            <?php endif ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        </div>
        <style>
            #attachments-for-links .chooser-thumb {
                height: 140px;
                display: flex;
                align-items: center;
                justify-content: center;
                overflow: hidden;
                background: var(--tblr-bg-surface-secondary, #f6f8fb);
                border-bottom: 1px solid var(--tblr-border-color, #e6e7e9);
            }
            #attachments-for-links .chooser-thumb img {
                max-width: 100%;
                max-height: 100%;
                object-fit: contain;
            }
            #attachments-for-links .chooser-missing {
                opacity: .7;
            }
        </style>
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
