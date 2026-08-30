<?php
/**
 * Admin stylesheets.
 *
 * `tabler.min.css` bundles Bootstrap 5.3, which is why no Bootstrap file is
 * listed here - Core/webroot/css/bootstrap.min.css is Bootstrap 4 and belongs to
 * the front-end theme only.
 *
 * @var \Croogo\Core\View\CroogoView $this
 */

if ($this->getRequest()->is('ajax')) {
    return;
}

echo $this->Html->css([
    'Croogo/Core.tabler/tabler.min',
    'Croogo/Core.tabler/tabler-icons.min',
    'Croogo/Core.core/select2.min.css',
    'Croogo/Core.core/tempusdominus-bootstrap-4.min',
    // Croogo's own layer: everything that reconciles the third-party widgets
    // above with Tabler lives here, so the vendored files stay pristine copies.
    'Croogo/Core.core/croogo-tabler',
]);

// Per-application override hook. Guarded because Cake serves plugin and app
// assets through AssetMiddleware, which logs a MissingRouteException for a file
// that is not there - and most applications never add one.
if (file_exists(WWW_ROOT . 'css' . DS . 'admin.css')) {
    echo $this->Html->css('/css/admin');
}
