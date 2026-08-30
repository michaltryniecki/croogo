<?php
/**
 * Installer layout.
 *
 * Uses the same Tabler assets as the admin panel rather than a stripped-down set
 * of its own: the installer is the first admin screen anyone sees, and it is
 * built from the same FormHelper, so a separate stylesheet would only mean a
 * second look to keep in sync.
 *
 * @var \Croogo\Core\View\CroogoView $this
 */
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="viewport-fit=cover, width=device-width, initial-scale=1.0">
        <title><?= __d('croogo', 'Installation: %s', $this->fetch('title')) ?> - <?= __d('croogo', 'Croogo') ?></title>
        <?php
        echo $this->Html->css([
            'Croogo/Core.tabler/tabler.min',
            'Croogo/Core.tabler/tabler-icons.min',
            'Croogo/Core.core/select2.min.css',
            'Croogo/Core.core/croogo-tabler',
        ]);
        echo $this->Html->script([
            'Croogo/Core.jquery/jquery.min',
            'Croogo/Core.tabler/bootstrap.bundle.min.js',
            'Croogo/Core.core/select2.full.min',
        ], ['async' => false]);
        echo $this->fetch('script');
        ?>
    </head>
    <body class="d-flex flex-column">
        <div class="page page-center">
            <div class="container container-tight py-4">
                <div class="text-center mb-4">
                    <span class="navbar-brand navbar-brand-autodark fs-2">
                        <?= __d('croogo', 'Install Croogo') ?>
                    </span>
                </div>
                <?= $this->fetch('before') ?>
                <div class="card card-md">
                    <div class="card-header">
                        <h3 class="card-title">
                            <?= __d('croogo', 'Installation: %s', $this->fetch('title')) ?>
                        </h3>
                    </div>
                    <div class="card-body">
                        <?php
                        echo $this->element('installer_steps');
                        echo $this->Layout->sessionFlash();
                        echo $this->fetch('content');
                        ?>
                    </div>
                    <?php
                    if ($buttons = $this->fetch('buttons')) {
                        echo $this->Html->div('card-footer text-end', $buttons);
                    }
                    ?>
                </div>
                <?= $this->fetch('after') ?>
            </div>
        </div>
        <?php
        $script = <<<EOF
\$('select:not(".no-select2")').select2({
    dropdownAutoWidth: true,
    theme: 'tabler'
});
EOF;
        $this->Js->buffer($script);

        echo $this->Js->writeBuffer();
        ?>
    </body>
</html>
