<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Query;
use Doctrine\ORM\Query\AST;
use Doctrine\ORM\Query\AST\SelectClause;
use Doctrine\ORM\Query\TreeWalkerAdapter;
use Doctrine\Tests\Models\ECommerce\ECommerceCart;
use Doctrine\Tests\Models\ECommerce\ECommerceCustomer;
use Doctrine\Tests\OrmFunctionalTestCase;
use ProxyManager\Proxy\GhostObjectInterface;

class DDC736Test extends OrmFunctionalTestCase
{
    protected function setUp() : void
    {
        $this->useModelSet('ecommerce');
        parent::setUp();
    }

    /**
     * @group DDC-736
     */
    public function testReorderEntityFetchJoinForHydration() : void
    {
        $cust = new ECommerceCustomer();
        $cust->setName('roman');

        $cart = new ECommerceCart();
        $cart->setPayment('cash');
        $cart->setCustomer($cust);

        $this->em->persist($cust);
        $this->em->persist($cart);
        $this->em->flush();
        $this->em->clear();

        $result = $this->em->createQuery('select c, c.name, ca, ca.payment from Doctrine\Tests\Models\ECommerce\ECommerceCart ca join ca.customer c')
            ->getSingleResult(/*\Doctrine\ORM\Query::HYDRATE_ARRAY*/);

        $cart2 = $result[0];
        unset($result[0]);

        self::assertInstanceOf(ECommerceCart::class, $cart2);
        self::assertNotInstanceOf(GhostObjectInterface::class, $cart2->getCustomer());
        self::assertInstanceOf(ECommerceCustomer::class, $cart2->getCustomer());
        self::assertEquals(['name' => 'roman', 'payment' => 'cash'], $result);
    }

    /**
     * @group DDC-736
     * @group DDC-925
     * @group DDC-915
     */
    public function testDqlTreeWalkerReordering() : void
    {
        $cust = new ECommerceCustomer();
        $cust->setName('roman');

        $cart = new ECommerceCart();
        $cart->setPayment('cash');
        $cart->setCustomer($cust);

        $this->em->persist($cust);
        $this->em->persist($cart);
        $this->em->flush();
        $this->em->clear();

        $dql    = 'select c, c.name, ca, ca.payment from Doctrine\Tests\Models\ECommerce\ECommerceCart ca join ca.customer c';
        $result = $this->em->createQuery($dql)
                            ->setHint(Query::HINT_CUSTOM_TREE_WALKERS, [DisableFetchJoinTreeWalker::class])
                            ->getResult();

        /* @var $cart2 ECommerceCart */
        $cart2 = $result[0][0];
        self::assertInstanceOf(GhostObjectInterface::class, $cart2->getCustomer());
    }
}

class DisableFetchJoinTreeWalker extends TreeWalkerAdapter
{
    public function walkSelectStatement(AST\SelectStatement $AST)
    {
        $this->walkSelectClause($AST->selectClause);
    }

    /**
     * @param SelectClause $selectClause
     */
    public function walkSelectClause($selectClause)
    {
        foreach ($selectClause->selectExpressions as $key => $selectExpr) {
            /* @var $selectExpr \Doctrine\ORM\Query\AST\SelectExpression */
            if ($selectExpr->expression === 'c') {
                unset($selectClause->selectExpressions[$key]);
                break;
            }
        }
    }
}
