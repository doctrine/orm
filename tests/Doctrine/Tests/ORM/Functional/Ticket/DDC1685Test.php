<?php
namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Tests\Models\DDC117\DDC117ArticleDetails;
use Doctrine\Tests\Models\DDC117\DDC117Article;
use Doctrine\ORM\Tools\Pagination\Paginator;

/**
 * @group DDC-1685
 */
class DDC1685Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    private $paginator;

    protected function setUp()
    {
        $this->useModelSet('ddc117');
        parent::setUp();

        $this->_em->createQuery('DELETE FROM Doctrine\Tests\Models\DDC117\DDC117ArticleDetails ad')->execute();

        $article = new DDC117Article("Foo");
        $this->_em->persist($article);
        $this->_em->flush();

        $articleDetails = new DDC117ArticleDetails($article, "Very long text");
        $this->_em->persist($articleDetails);
        $this->_em->flush();

        $dql   = "SELECT ad FROM Doctrine\Tests\Models\DDC117\DDC117ArticleDetails ad";
        $query = $this->_em->createQuery($dql);

        $this->paginator = new Paginator($query);
    }

    public function testPaginateCount()
    {
        $this->assertEquals(1, count($this->paginator));
    }

    public function testPaginateIterate()
    {
        foreach ($this->paginator as $ad) {
            $this->assertInstanceOf('Doctrine\Tests\Models\DDC117\DDC117ArticleDetails', $ad);
        }
    }

    public function testPaginateCountNoOutputWalkers()
    {
        $this->paginator->setUseOutputWalkers(false);
        $this->assertEquals(1, count($this->paginator));
    }

    public function testPaginateIterateNoOutputWalkers()
    {
        $this->paginator->setUseOutputWalkers(false);

        $this->setExpectedException('RuntimeException', 'Paginating an entity with foreign key as identifier only works when using the Output Walkers. Call Paginator#setUseOutputWalkers(true) before iterating the paginator.');
        foreach ($this->paginator as $ad) {
            $this->assertInstanceOf('Doctrine\Tests\Models\DDC117\DDC117ArticleDetails', $ad);
        }
    }
}

