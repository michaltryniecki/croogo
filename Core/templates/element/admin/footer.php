<?php
/**
 * @var \Croogo\Core\View\CroogoView $this
 */

use Cake\Core\Configure;

$link = $this->Html->link(
    __d('croogo', 'Croogo %s', (string)Configure::read('Croogo.version')),
    'https://www.croogo.org',
    ['target' => '_blank', 'rel' => 'noopener']
);
?>
<footer class="footer footer-transparent d-print-none">
    <div class="container-fluid">
        <div class="row text-center align-items-center flex-row-reverse">
            <div class="col-12 col-lg-auto mt-3 mt-lg-0">
                <ul class="list-inline list-inline-dots mb-0">
                    <li class="list-inline-item text-secondary">
                        <?= __d('croogo', 'Powered by %s', $link) ?>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</footer>
