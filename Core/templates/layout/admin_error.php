<?php
/**
 * Admin error layout.
 *
 * No sidebar: an error page is often thrown by something that failed before the
 * menus could be built, so rendering them here risks a second failure on top of
 * the one being reported.
 *
 * @var \Croogo\Core\View\CroogoView $this
 */
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="viewport-fit=cover, width=device-width, initial-scale=1.0">
        <title><?= $this->fetch('title') ?> - <?= $_siteTitle ?></title>
        <?php

        echo $this->element('admin/stylesheets');
        echo $this->element('admin/javascripts');

        echo $this->fetch('script');
        echo $this->fetch('css');

        ?>
    </head>
    <body>
        <div class="page">
            <div class="page-wrapper">
                <div class="page-body">
                    <div class="<?= $this->Theme->getCssClass('containerFluid') ?>">
                        <?= $this->Layout->sessionFlash() ?>
                        <?= $this->fetch('content') ?>
                    </div>
                </div>
                <?= $this->element('Croogo/Core.admin/footer') ?>
            </div>
        </div>
        <?php
        echo $this->element('Croogo/Core.admin/initializers');
        echo $this->fetch('body-footer');

        echo $this->fetch('postLink');
        echo $this->fetch('scriptBottom');
        echo $this->Js->writeBuffer();
        ?>
    </body>
</html>
