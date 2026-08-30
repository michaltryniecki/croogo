<?php
/**
 * Popup/iframe layout - no navigation, just the page body.
 *
 * @var \Croogo\Core\View\CroogoView $this
 */

$showActions = isset($showActions) ? $showActions : true;
$pageTitle = trim($this->fetch('title'));
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="viewport-fit=cover, width=device-width, initial-scale=1.0">
        <title><?= $pageTitle ?> - <?= $_siteTitle ?></title>
        <?php

        echo $this->element('admin/stylesheets');
        echo $this->element('admin/javascripts');

        echo $this->fetch('script');
        echo $this->fetch('css');
        ?>
    </head>
    <body class="popup">
        <div class="page">
            <div class="page-wrapper">
                <div class="page-header">
                    <div class="<?= $this->Theme->getCssClass('container') ?>">
                        <div class="row g-2 align-items-center">
                            <div class="col">
                                <?= $this->element('Croogo/Core.admin/breadcrumb') ?>
                                <h2 class="page-title"><?= $pageTitle ?></h2>
                            </div>
                            <?php if ($showActions && $actionsBlock = $this->fetch('action-buttons')) : ?>
                                <div class="col-auto ms-auto">
                                    <div class="btn-list"><?= $actionsBlock ?></div>
                                </div>
                            <?php endif ?>
                        </div>
                    </div>
                </div>
                <div class="page-body">
                    <div class="<?= $this->Theme->getCssClass('container') ?>">
                        <?= $this->Layout->sessionFlash() ?>
                        <?= $this->fetch('content') ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
        echo $this->element('Croogo/Core.admin/initializers');
        echo $this->fetch('postLink');
        echo $this->fetch('scriptBottom');
        echo $this->Js->writeBuffer();
        ?>
    </body>
</html>
