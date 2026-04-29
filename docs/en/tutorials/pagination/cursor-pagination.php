<?php

use Doctrine\ORM\Tools\Pagination\CursorPaginator;

$cursor = $_GET['cursor'] ?? null;

$query = $entityManager->createQuery('SELECT p FROM BlogPost p ORDER BY p.createdAt DESC, p.id DESC');

/** @var CursorPaginator<BlogPost> $paginator */
$paginator = (new CursorPaginator($query))
    ->paginate(cursor: $cursor, limit: 15);
?>
<p><?= $paginator->getTotalCount() ?> result(s) in total, <?= $paginator->countPageItems() ?> on this page.</p>

<ul>
    <?php foreach ($paginator as $post): ?>
        <li><?= escape($post->getTitle()) ?></li>
    <?php endforeach ?>
</ul>

<?php if ($paginator->hasToPaginate()): ?>
    <nav>
        <?php if ($paginator->hasPreviousPage()): ?>
            <a href="?cursor=<?= escape($paginator->getPreviousCursorAsString()) ?>">Previous</a>
        <?php endif ?>

        <?php if ($paginator->hasNextPage()): ?>
            <a href="?cursor=<?= escape($paginator->getNextCursorAsString()) ?>">Next</a>
        <?php endif ?>
    </nav>
<?php endif ?>
