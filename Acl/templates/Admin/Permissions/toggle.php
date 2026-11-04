<?php

use Cake\Core\Configure;

if (!empty($damaged)) {
    // HTTP 200 like every other outcome: the screen puts this fragment in the cell it clicked
    $message = __d('croogo', 'The ACO tree is damaged here (lft/rght do not match parent_id), so a toggle would change another action. Repair the tree first.');
    echo $this->Html->tag('i', '', [
        'class' => 'text-warning fa fa-exclamation-triangle',
        'title' => $message,
        'aria-label' => $message,
    ]);
} elseif ($success == 1) {
    if ($permitted == 1) {
        echo $this->Html->icon($this->Theme->getIcon('check-mark'), [
            'class' => 'permission-toggle green',
            'data-aco_id' => $acoId,
            'data-aro_id' => $aroId
        ]);
    } else {
        echo $this->Html->icon($this->Theme->getIcon('x-mark'), [
            'class' => 'permission-toggle red',
            'data-aco_id' => $acoId,
            'data-aro_id' => $aroId
        ]);
    }
} else {
    echo __d('croogo', 'error');
}

Configure::write('debug', 0);
