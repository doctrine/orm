<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Tests\Models\ECommerce\ECommerceCart;
use Doctrine\Tests\Models\ECommerce\ECommerceCustomer;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\AST;

require_once __DIR__ . '/../../../TestInit.php';

class DDC736Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    protected function setUp()
    {
        $this->useModelSet('ecommerce');
        parent::setUp();
    }

    /**
     * @group DDC-736
     */
    public function testReorderEntityFetchJoinForHydration()
    {
        $cust = new ECommerceCustomer;
        $cust->setName('roman');
        
        $cart = new ECommerceCart;
        $cart->setPayment('cash');
        $cart->setCustomer($cust);
        
        $this->_em->persist($cust);
        $this->_em->persist($cart);
        $this->_em->flush();
        $this->_em->clear();

        $result = $this->_em->createQuery("select c, c.name, ca, ca.payment from Doctrine\Tests\Models\ECommerce\ECommerceCart ca join ca.customer c")
            ->getSingleResult(/*\Doctrine\ORM\Query::HYDRATE_ARRAY*/);
        
        $cart2 = $result[0];
        unset($result[0]);

        $this->assertInstanceOf('Doctrine\Tests\Models\ECommerce\ECommerceCart', $cart2);
        $this->assertNotInstanceOf('Doctrine\ORM\Proxy\Proxy', $cart2->getCustomer());
        $this->assertInstanceOf('Doctrine\Tests\Models\ECommerce\ECommerceCustomer', $cart2->getCustomer());
        $this->assertEquals(array('name' => 'roman', 'payment' => 'cash'), $result);
    }

    /**
     * @group DDC-736
     * @group DDC-925
     * @group DDC-915
     */
    public function testDqlTreeWalkerReordering()
    {
        $cust = new ECommerceCustomer;
        $cust->setName('roman');

        $cart = new ECommerceCart;
        $cart->setPayment('cash');
        $cart->setCustomer($cust);

        $this->_em->persist($cust);
        $this->_em->persist($cart);
        $this->_em->flush();
        $this->_em->clear();

        $dql = "select c, c.name, ca, ca.payment from Doctrine\Tests\Models\ECommerce\ECommerceCart ca join ca.customer c";
        $result = $this->_em->createQuery($dql)
                            ->setHint(Query::HINT_CUSTOM_TREE_WALKERS, array('Doctrine\Tests\ORM\Functional\Ticket\DisableFetchJoinTreeWalker'))
                            ->getResult();

        /* @var $cart2 Doctrine\Tests\Models\ECommerce\ECommerceCart */
        $cart2 = $result[0][0];
        $this->assertInstanceOf('Doctrine\ORM\Proxy\Proxy', $cart2->getCustomer());
    }
}

class DisableFetchJoinTreeWalker extends \Doctrine\ORM\Query\TreeWalkerAdapter
{
    public function walkSelectStatement(AST\SelectStatement $AST)
    {
        $this->walkSelectClause($AST->selectClause);
    }

    /**
     * @param \Doctrine\ORM\Query\AST\SelectClause $selectClause
     */
    public function walkSelectClause($selectClause)
    {
        foreach ($selectClause->selectExpressions AS $key => $selectExpr) {
            /* @var $selectExpr \Doctrine\ORM\Query\AST\SelectExpression */
            if ($selectExpr->expression == "c") {
                unset($selectClause->selectExpressions[$key]);
                break;
            }
        }
    }
}

