<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Query;
use Doctrine\ORM\Tools\Pagination\OffsetPaginator;
use Doctrine\ORM\Tools\Pagination\Window;
use Doctrine\Tests\Models\DDC117\DDC117Article;
use Doctrine\Tests\Models\DDC117\DDC117ArticleDetails;
use Doctrine\Tests\OrmFunctionalTestCase;
use PHPUnit\Framework\Attributes\Group;
use RuntimeException;

#[Group('DDC-1685')]
class DDC1685Test extends OrmFunctionalTestCase
{
    private Query $query;
    private Window $window;

    protected function setUp(): void
    {
        $this->useModelSet('ddc117');

        parent::setUp();

        $this->_em->createQuery('DELETE FROM Doctrine\Tests\Models\DDC117\DDC117ArticleDetails ad')->execute();

        $article = new DDC117Article('Foo');
        $this->_em->persist($article);
        $this->_em->flush();

        $articleDetails = new DDC117ArticleDetails($article, 'Very long text');
        $this->_em->persist($articleDetails);
        $this->_em->flush();

        $dql = 'SELECT ad FROM Doctrine\Tests\Models\DDC117\DDC117ArticleDetails ad';

        $this->query  = $this->_em->createQuery($dql);
        $this->window = new Window(0, 1);
    }

    public function testPaginateCount(): void
    {
        $page = (new OffsetPaginator())->paginate($this->query, $this->window);

        self::assertSame(1, $page->getTotalCount());
    }

    public function testPaginateIterate(): void
    {
        $page = (new OffsetPaginator())->paginate($this->query, $this->window);

        foreach ($page as $ad) {
            self::assertInstanceOf(DDC117ArticleDetails::class, $ad);
        }
    }

    public function testPaginateCountNoOutputWalkers(): void
    {
        $page = (new OffsetPaginator(false, false))->paginate($this->query, $this->window);

        self::assertSame(1, $page->getTotalCount());
    }

    public function testPaginateIterateNoOutputWalkers(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Paginating an entity with foreign key as identifier only works when using the Output Walkers. Pass $useOutputWalkers = true to the paginator constructor.');

        (new OffsetPaginator(true, false))->paginate($this->query, $this->window);
    }
}
