<?php
use CodeIgniter\Pager\PagerRenderer;

/**

@var PagerRenderer $pager
*/
$pager->setSurroundCount(2);
?>
<nav aria-label="<?= lang('Pager.pageNavigation') ?>">
<ul class="pagination" style="flex-wrap: wrap;">
<!-- First Link -->
<?php if ($pager->hasPreviousPage()) : ?>
<li class="page-item">
<a href="<?= $pager->getFirst() ?>" class="page-link" aria-label="<?= lang('Pager.first') ?>">
<span aria-hidden="true"><?= lang('Pager.first') ?></span>
</a>
</li>
<li class="page-item">
<a href="<?= $pager->getPreviousPage() ?>" class="page-link" aria-label="<?= lang('Pager.previous') ?>">
<span><?= lang('Pager.previous') ?></span>
</a>
</li>
<?php else : ?>
<li class="page-item disabled">
<a class="page-link" href="#" tabindex="-1" aria-disabled="true"><?= lang('Pager.first') ?></a>
</li>
<li class="page-item disabled">
<a class="page-link" href="#" tabindex="-1" aria-disabled="true"><?= lang('Pager.previous') ?></a>
</li>
<?php endif ?>

<?php foreach ($pager->links() as $link) : ?>
        <li class="page-item <?= $link['active'] ? 'active' : '' ?>">
            <a href="<?= $link['uri'] ?>" class="page-link">
                <?= $link['title'] ?>
            </a>
        </li>
    <?php endforeach ?>

    <?php if ($pager->hasNextPage()) : ?>
        <li class="page-item">
            <a href="<?= $pager->getNextPage() ?>" class="page-link" aria-label="<?= lang('Pager.next') ?>">
                <span><?= lang('Pager.next') ?></span>
            </a>
        </li>
        <li class="page-item">
            <a href="<?= $pager->getLast() ?>" class="page-link" aria-label="<?= lang('Pager.last') ?>">
                <span aria-hidden="true"><?= lang('Pager.last') ?></span>
            </a>
        </li>
    <?php else : ?>
        <li class="page-item disabled">
            <a class="page-link" href="#" tabindex="-1" aria-disabled="true"><?= lang('Pager.next') ?></a>
        </li>
         <li class="page-item disabled">
            <a class="page-link" href="#" tabindex="-1" aria-disabled="true"><?= lang('Pager.last') ?></a>
        </li>
    <?php endif ?>
</ul>
</nav>
