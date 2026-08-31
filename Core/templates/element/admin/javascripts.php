<?php
/**
 * Admin javascript.
 *
 * Bootstrap's own bundle is loaded here and `@tabler/core`'s `tabler.min.js` is
 * NOT. That file bundles its own private copy of Bootstrap and registers a second
 * set of data-api handlers, so with both on the page every dropdown opened and
 * closed again on the same click. What it adds beyond Bootstrap is glue for
 * Tabler's demo widgets (autosize, countup, charts) that Croogo does not use.
 *
 * Bootstrap 5 dropped the jQuery plugin API, so anything below that used to drive
 * a Bootstrap component through jQuery has been ported to the `bootstrap.*`
 * globals - see core/modal.js and core/lightbox.js.
 *
 * jQuery itself stays: select2, typeahead and Croogo's own admin scripts are all
 * built on it.
 *
 * moment.js and moment-timezone went out with Tempus Dominus: the date fields are
 * native `<input type="datetime-local">` now and do their one bit of time-zone
 * arithmetic with `Intl` - see `Admin.dateTimeFields()`.
 *
 * @var \Croogo\Core\View\CroogoView $this
 */

if ($this->getRequest()->is('ajax')) {
    return;
}

echo $this->Layout->js();

echo $this->Html->script([
    'Croogo/Core.jquery/jquery.min.js',
    'Croogo/Core.core/underscore-min',
]);

echo $this->Html->script([
    'Croogo/Core.jquery/jquery-ui.min.js',
    'Croogo/Core.tabler/bootstrap.bundle.min.js',
    'Croogo/Core.jquery/jquery.slug',
    'Croogo/Core.jquery/jquery.hoverIntent.minified',
    'Croogo/Core.core/bootstrap3-typeahead.min',
    'Croogo/Core.core/typeahead_autocomplete',
    'Croogo/Core.core/select2.full.min.js',
    'Croogo/Core.core/lightbox',
    'Croogo/Core.core/choose',
    'Croogo/Core.core/modal',
], [
    'async' => false,
]);

echo $this->Html->script([
    'Croogo/Core.core/admin',
], [
    'defer' => false,
]);

// Per-application override hook, mirroring the stylesheet one.
if (file_exists(WWW_ROOT . 'js' . DS . 'admin.js')) {
    echo $this->Html->script('/js/admin', ['async' => false]);
}
