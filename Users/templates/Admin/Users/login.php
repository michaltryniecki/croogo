<?php

use Cake\Core\Configure;

$this->assign('title', __d('croogo', 'Login'));

$formStart = $this->Form->create(null, ['url' => ['action' => 'login']]);
$body = $this->Form->input('username', [
    'placeholder' => __d('croogo', 'Username'),
    'label' => false,
    'prepend' => $this->Html->icon('user'),
    'required' => true,
]);
$body .= $this->Form->input('password', [
    'placeholder' => __d('croogo', 'Password'),
    'label' => false,
    'prepend' => $this->Html->icon('key'),
    'required' => true,
]);
if (Configure::read('Access Control.autoLoginDuration')) :
    $body .= $this->Form->input('remember', [
        'label' => __d('croogo', 'Remember me?'),
        'type' => 'checkbox',
        'default' => false,
    ]);
endif;

$footer = $this->Html->link(__d('croogo', 'Forgot password?'), [
    'prefix' => 'Admin',
    'plugin' => 'Croogo/Users',
    'controller' => 'Users',
    'action' => 'forgot',
], [
    'class' => 'forgot',
]);
$footer .= $this->Form->button(
    $this->Html->icon('login', ['class' => 'me-1']) . __d('croogo', 'Log In'),
    ['class' => 'btn btn-primary', 'escapeTitle' => false]
);
$formEnd = $this->Form->end();

?>
<div class="card card-md">
    <?= $formStart ?>
    <div class="card-body">
        <?php
        echo $this->Layout->sessionFlash();
        echo $body;
        ?>
    </div>
    <div class="card-footer">
        <div class="d-flex align-items-center justify-content-between gap-2">
            <?= $footer ?>
        </div>
    </div>
    <?= $formEnd ?>
</div>
