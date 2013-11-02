<?php

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Tests\Models\StockExchange\Stock;
use Doctrine\Tests\Models\StockExchange\Market;
use Doctrine\Tests\Models\StockExchange\Bond;

require_once __DIR__ . '/../../TestInit.php';

/**
 * @group DDC-250
 */
class IndexByAssociationTest extends \Doctrine\Tests\OrmFunctionalTestCase
{
    /**
     * @var Doctrine\Tests\Models\StockExchange\Market
     */
    private $market;

    private $bond;

    public function setUp()
    {
        $this->useModelSet('stockexchange');
        parent::setUp();
        $this->loadFixture();
    }

    public function loadFixture()
    {
        $this->market = new Market("Some Exchange");
        $stock1 = new Stock("AAPL", 10, $this->market);
        $stock2 = new Stock("GOOG", 20, $this->market);

        $this->bond = new Bond("MyBond");
        $this->bond->addStock($stock1);
        $this->bond->addStock($stock2);

        $this->_em->persist($this->market);
        $this->_em->persist($stock1);
        $this->_em->persist($stock2);
        $this->_em->persist($this->bond);
        $this->_em->flush();
        $this->_em->clear();
    }

    public function testManyToOneFinder()
    {
        /* @var $market Doctrine\Tests\Models\StockExchange\Market */
        $market = $this->_em->find('Doctrine\Tests\Models\StockExchange\Market', $this->market->getId());

        $this->assertEquals(2, count($market->stocks));
        $this->assertTrue(isset($market->stocks['AAPL']), "AAPL symbol has to be key in indexed association.");
        $this->assertTrue(isset($market->stocks['GOOG']), "GOOG symbol has to be key in indexed association.");
        $this->assertEquals("AAPL", $market->stocks['AAPL']->getSymbol());
        $this->assertEquals("GOOG", $market->stocks['GOOG']->getSymbol());
    }

    public function testManyToOneDQL()
    {
        $dql = "SELECT m, s FROM Doctrine\Tests\Models\StockExchange\Market m JOIN m.stocks s WHERE m.id = ?1";
        $market = $this->_em->createQuery($dql)->setParameter(1, $this->market->getId())->getSingleResult();

        $this->assertEquals(2, count($market->stocks));
        $this->assertTrue(isset($market->stocks['AAPL']), "AAPL symbol has to be key in indexed association.");
        $this->assertTrue(isset($market->stocks['GOOG']), "GOOG symbol has to be key in indexed association.");
        $this->assertEquals("AAPL", $market->stocks['AAPL']->getSymbol());
        $this->assertEquals("GOOG", $market->stocks['GOOG']->getSymbol());
    }

    public function testManyToMany()
    {
        $bond = $this->_em->find('Doctrine\Tests\Models\StockExchange\Bond', $this->bond->getId());

        $this->assertEquals(2, count($bond->stocks));
        $this->assertTrue(isset($bond->stocks['AAPL']), "AAPL symbol has to be key in indexed association.");
        $this->assertTrue(isset($bond->stocks['GOOG']), "GOOG symbol has to be key in indexed association.");
        $this->assertEquals("AAPL", $bond->stocks['AAPL']->getSymbol());
        $this->assertEquals("GOOG", $bond->stocks['GOOG']->getSymbol());
    }

    public function testManytoManyDQL()
    {
        $dql = "SELECT b, s FROM Doctrine\Tests\Models\StockExchange\Bond b JOIN b.stocks s WHERE b.id = ?1";
        $bond = $this->_em->createQuery($dql)->setParameter(1, $this->bond->getId())->getSingleResult();

        $this->assertEquals(2, count($bond->stocks));
        $this->assertTrue(isset($bond->stocks['AAPL']), "AAPL symbol has to be key in indexed association.");
        $this->assertTrue(isset($bond->stocks['GOOG']), "GOOG symbol has to be key in indexed association.");
        $this->assertEquals("AAPL", $bond->stocks['AAPL']->getSymbol());
        $this->assertEquals("GOOG", $bond->stocks['GOOG']->getSymbol());
    }

    public function testDqlOverrideIndexBy()
    {
        $dql = "SELECT b, s FROM Doctrine\Tests\Models\StockExchange\Bond b JOIN b.stocks s INDEX BY s.id WHERE b.id = ?1";
        $bond = $this->_em->createQuery($dql)->setParameter(1, $this->bond->getId())->getSingleResult();

        $this->assertEquals(2, count($bond->stocks));
        $this->assertFalse(isset($bond->stocks['AAPL']), "AAPL symbol not exists in re-indexed association.");
        $this->assertFalse(isset($bond->stocks['GOOG']), "GOOG symbol not exists in re-indexed association.");
    }
}

