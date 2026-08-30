<?php

$this->setLayout('admin_login');

$title = __d('croogo', 'Forgot Password');
$this->assign('title', $title);

$formStart = $this->Form->create(null, [
    'url' => [
        'controller' => 'Users',
        'action' => 'forgot',
    ],
]);

    $body = $this->Form->input('username', [
        'label' => false,
        'placeholder' => __d('croogo', 'Username/Email'),
        'prepend' => $this->Html->icon('user'),
        'required' => true,
    ]);
    $footer = $this->Form->input(__d('croogo', 'Submit'), [
        'type' => 'submit',
        'class' => 'btn btn-primary',
    ]);
    $formEnd = $this->Form->end();

    ?>
<div class="card card-md">
    <div class="card-header">
        <h5 class="card-title"><?= $this->fetch('title') ?></h5>
    </div>
    <?= $formStart ?>
    <div class="card-body">
        <?php
        echo $this->Layout->sessionFlash();
        echo $body;
        ?>
    </div>
    <div class="card-footer d-flex align-items-center justify-content-between gap-2">
        <?= $footer ?>
    </div>
    <?= $formEnd ?>
</div>
