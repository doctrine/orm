<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Tests\Models\DDC117\DDC117Article;
use Doctrine\Tests\Models\DDC117\DDC117ArticleDetails;
use Doctrine\Tests\OrmFunctionalTestCase;
use PHPUnit\Framework\Attributes\Group;
use RuntimeException;

#[Group('DDC-1685')]
class DDC1685Test extends OrmFunctionalTestCase
{
    private Paginator $paginator;

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

        $dql   = 'SELECT ad FROM Doctrine\Tests\Models\DDC117\DDC117ArticleDetails ad';
        $query = $this->_em->createQuery($dql);
        $query->setMaxResults(1);

        $this->paginator = new Paginator($query);
    }

    public function testPaginateCount(): void
    {
        self::assertCount(1, $this->paginator);
    }

    public function testPaginateIterate(): void
    {
        foreach ($this->paginator as $ad) {
            self::assertInstanceOf(DDC117ArticleDetails::class, $ad);
        }
    }

    public function testPaginateCountNoOutputWalkers(): void
    {
        $this->paginator->setUseOutputWalkers(false);
        self::assertCount(1, $this->paginator);
    }

    public function testPaginateIterateNoOutputWalkers(): void
    {
        $this->paginator->setUseOutputWalkers(false);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Paginating an entity with foreign key as identifier only works when using the Output Walkers. Call Paginator#setUseOutputWalkers(true) before iterating the paginator.');

        foreach ($this->paginator as $ad) {
            self::assertInstanceOf(DDC117ArticleDetails::class, $ad);
        }
    }
}
