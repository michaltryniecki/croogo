<?php

$this->Html->css([
    'bootstrap.min',
    'font-awesome.min',
    // The Flash elements are shared with the admin panel and draw their icons
    // from the theme's icon map, which is Tabler Icons. Font Awesome above stays
    // for this theme's own markup.
    'tabler/tabler-icons.min',
    '//fonts.googleapis.com/css?family=Montserrat:400,700',
    '//fonts.googleapis.com/css?family=Delius',
    '//fonts.googleapis.com/css?family=Droid+Serif:400,700,400italic,700italic',
    '//fonts.googleapis.com/css?family=Roboto+Slab:400,100,300,700',
    'theme.min',
    'custom',
], [
    'block' => 'css',
]);
