<?php
/**
 * Full-width admin layout: the page chrome without the sidebar.
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
            <?= $this->element('Croogo/Core.admin/header') ?>
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
        echo $this->Blocks->get('scriptBottom');
        echo $this->Js->writeBuffer();
        ?>
    </body>
</html>
