<?php

// Keeps the theme's own table classes so the grid still sits flush in its
// card; `permission-table` only adds the ACL-specific behaviour styles.
$this->set('tableClass', $this->Theme->getCssClass('tableClass') . ' permission-table');

$roleTitles = array_values($roles->toArray());
$roleIds = array_keys($roles->toArray());

$tableHeaders = [
    __d('croogo', 'Id'),
    __d('croogo', 'Alias'),
];
$tableHeaders = array_merge($tableHeaders, $roleTitles);
$tableHeaders = $this->Html->tableHeaders($tableHeaders);

echo $this->Html->tag('thead', $tableHeaders);

$currentController = '';
$icon = '<i class="ti ti-chevron-right float-end perm-icon"></i>';
foreach ($acos as $aco) {
    $id = $aco->id;
    $alias = $aco->alias;
    $class = '';
    if (substr($alias, 0, 1) == '_') {
        $level = 1;
        $class .= 'level-' . $level;
        $oddOptions = ['class' => 'd-none controller-' . $currentController];
        $evenOptions = ['class' => 'd-none controller-' . $currentController];
        $alias = substr_replace($alias, '', 0, 1);
    } else {
        $level = 0;
        $class .= ' controller';
        if ($aco->children > 0) {
            $class .= ' perm-expand';
        }
        $oddOptions = [];
        $evenOptions = [];
        $currentController = $alias;
    }

    $row = [
        $id,
        $this->Html->div(trim($class), $alias . $icon, [
            'data-id' => $id,
            'data-alias' => $alias,
            'data-level' => $level,
        ]),
    ];

    foreach ($roles as $roleId => $roleTitle) {
        $row[] = '';
    }

    echo $this->Html->tableCells($row, $oddOptions, $evenOptions);
}
echo $this->Html->tag('thead', $tableHeaders);
