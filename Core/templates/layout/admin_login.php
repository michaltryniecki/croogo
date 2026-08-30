<?php
/**
 * Login layout - Tabler's centred single-card page.
 *
 * @var \Croogo\Core\View\CroogoView $this
 */

use Cake\Core\Configure;

?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="viewport-fit=cover, width=device-width, initial-scale=1.0">
        <title><?= $this->fetch('title') ?> - <?= $_siteTitle ?></title>
        <?php
        // The full admin stylesheet, not a cut-down one: the login form is built
        // by the same FormHelper as every other admin form, so it needs the same
        // component styles to look like anything at all.
        echo $this->element('admin/stylesheets');
        echo $this->element('admin/javascripts');

        echo $this->fetch('script');
        echo $this->fetch('css');
        ?>
    </head>
    <body class="d-flex flex-column">
        <div class="page page-center">
            <div class="container container-tight py-4">
                <div class="text-center mb-4">
                    <?= $this->Html->link(
                        h(Configure::read('Site.title')),
                        '/',
                        ['class' => 'navbar-brand navbar-brand-autodark fs-2']
                    ) ?>
                </div>
                <?= $this->Layout->sessionFlash() ?>
                <?= $this->fetch('content') ?>
            </div>
        </div>
        <?php
        echo $this->fetch('postLink');
        echo $this->fetch('scriptBottom');
        echo $this->Js->writeBuffer();
        ?>
    </body>
</html>
