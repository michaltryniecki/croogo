<?php
/**
 * Layout for XHR responses: the rendered view and nothing else.
 *
 * Croogo asks for this by name - `viewBuilder()->setLayout('ajax')` in
 * AppController, CroogoComponent, ToggleAction and the attachments controller -
 * so CakePHP looks for `layout/ajax.php`. `layout/ajax/default.php` next to this
 * file is a different thing: that one is only reached through the `ajax` VIEW
 * TYPE (`setLayoutPath('ajax')`), which nothing here sets. Without this file every
 * one of those actions dies with a MissingLayoutException - which is why the ACL
 * permissions matrix used to sit on its loading spinner forever.
 *
 * @var \Croogo\Core\View\CroogoView $this
 */

echo $this->fetch('content');
