<?php

$activeThemes = [$currentTheme['name'], $currentBackendTheme['name']];

?>
<div class="card">

    <?php
    if (!empty($theme['screenshot'])) :
        $dataUri = $this->Croogo->dataUri($theme['name'], $theme['screenshot']);
        $thumbnail = '<img class="card-img-top" src="' . $dataUri . '">';
        // `data-title` is what core/lightbox.js uses to caption the dialog.
        $image = sprintf(
            '<a href="%s" data-toggle="lightbox" data-title="%s">%s</a>',
            $dataUri,
            h($theme['name']),
            $thumbnail
        );
        echo $image;
    endif;
    ?>

    <div class="card-body">

        <h5 class="card-title">
            <?php
            $author = isset($theme['author']) ? $theme['author'] : null;
            if (isset($theme['authorUrl']) && strlen($theme['authorUrl']) > 0) {
                $author = $this->Html->link($author, $theme['authorUrl']);
            }
            echo $theme['name'];
            if (!empty($author)) :
                echo ' ' . __d('croogo', 'by') . ' ' . $author;
            endif;
            ?>
        </h5>

        <?php
            $badge = '';
        if ($theme['name'] == $currentTheme['name']) :
            $badge .= $this->Html->tag('span', __d('croogo', 'Current Frontend Theme'), ['class' => 'badge text-bg-success']);
        endif;
        if ($theme['name'] == $currentBackendTheme['name']) :
            $badge .= $this->Html->tag('span', __d('croogo', 'Current Backend Theme'), ['class' => 'badge text-bg-success']);
        endif;
        if ($badge) :
            // `.badges-list` is Tabler's inline badge row - without it the two
            // badges sit flush against each other.
            echo $this->Html->div('badges-list mb-2', $badge);
        endif;
        ?>

        <p class="card-text"><?= $theme['description'] ?></p>
        <?php if (isset($theme['regions'])) : ?>
            <p class="regions"><?= __d('croogo', 'Regions supported: ') .
                    implode(', ', $theme['regions']) ?></p>
        <?php endif ?>

   </div>

<?php


    $out = '';
if ($theme['isFrontendTheme'] && $currentTheme['name'] != $theme['name']) :
    $out .= $this->Form->postLink(__d('croogo', 'Activate Frontend'), [
            'action' => 'activate',
            'theme' => $theme['name'],
        ], [
            'button' => 'outline-secondary btn-sm',
            'icon' => $this->Theme->getIcon('power-on'),
            'escape' => false,
        ]);
endif;

if ($theme['isBackendTheme'] && $currentBackendTheme['name'] != $theme['name']) :
    $out .= $this->Form->postLink(__d('croogo', 'Activate Backend'), [
            'action' => 'activate',
            'theme' => $theme['name'],
            'type' => 'admin_theme',
        ], [
            'button' => 'outline-secondary btn-sm',
            'icon' => $this->Theme->getIcon('power-on'),
            'escape' => false,
        ]);
endif;

if (!in_array($theme['name'], $activeThemes)) :
    $out .= $this->Form->postLink(__d('croogo', 'Delete'), [
            'action' => 'delete',
            'theme' => $theme['name'],
        ], [
            'button' => 'outline-danger btn-sm',
            'escape' => true,
            'escapeTitle' => false,
            'icon' => $this->Theme->getIcon('delete'),
        ], __d('croogo', 'Are you sure?'));
endif;

if (!empty($out)) :
    echo $this->Html->div('card-footer', $this->Html->div('btn-list justify-content-end', $out));
endif;
?>
</div>
