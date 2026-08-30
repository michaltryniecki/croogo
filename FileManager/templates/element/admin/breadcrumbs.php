<ul class="breadcrumb mb-0">
    <?php $breadcrumb = $this->FileManager->breadcrumb($path) ?>
    <?php foreach ($breadcrumb as $pathname => $p) : ?>
        <li class="breadcrumb-item"><?= $this->FileManager->linkDirectory($pathname, $p) ?></li>
    <?php endforeach ?>
</ul>
