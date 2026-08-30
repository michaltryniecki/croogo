<?php
/**
 * `Admin.navigation()` is gone with core/sidebar.js: the sidebar is Bootstrap 5
 * dropdown markup now, which needs no initialisation.
 *
 * @var \Croogo\Core\View\CroogoView $this
 */

$adminThemeScripts = <<<EOF
    Admin.form();
    Admin.protectForms();
    Admin.formFeedback();
    Admin.extra();
    Admin.slideBoxToggle();
    Admin.dateTimeFields();
    Admin.lightbox();
    Admin.modal();

EOF;

if (!$this->getRequest()->is('ajax')) :
    $this->Js->buffer($adminThemeScripts);
endif;
