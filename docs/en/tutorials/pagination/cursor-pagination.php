<?php

use Doctrine\ORM\Tools\Pagination\CursorPaginator;

$cursor = $_GET['cursor'] ?? null;

$query = $entityManager->createQuery('SELECT p FROM BlogPost p ORDER BY p.createdAt DESC, p.id DESC');

/** @var CursorPaginator<BlogPost> $paginator */
$paginator = new CursorPaginator(limit: 15);

$page = $paginator->paginate($query, $cursor);
?>
<p><?= $page->getTotalCount() ?> result(s) in total, <?= $page->count() ?> on this page.</p>

<ul>
    <?php foreach ($page as $post): ?>
        <li><?= escape($post->getTitle()) ?></li>
    <?php endforeach ?>
</ul>

<?php if ($page->hasToPaginate()): ?>
    <nav>
        <?php if ($page->hasPreviousPage()): ?>
            <a href="?cursor=<?= escape($page->getPreviousCursorAsString()) ?>">Previous</a>
        <?php endif ?>

        <?php if ($page->hasNextPage()): ?>
            <a href="?cursor=<?= escape($page->getNextCursorAsString()) ?>">Next</a>
        <?php endif ?>
    </nav>
<?php endif ?>
