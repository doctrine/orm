<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Proxy\Proxy;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\AST;
use Doctrine\ORM\Query\AST\SelectClause;
use Doctrine\ORM\Query\AST\SelectExpression;
use Doctrine\ORM\Query\TreeWalkerAdapter;
use Doctrine\Tests\Models\ECommerce\ECommerceCart;
use Doctrine\Tests\Models\ECommerce\ECommerceCustomer;
use Doctrine\Tests\OrmFunctionalTestCase;

use function assert;

class DDC736Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        $this->useModelSet('ecommerce');
        parent::setUp();
    }

    /**
     * @group DDC-736
     */
    public function testReorderEntityFetchJoinForHydration(): void
    {
        $cust = new ECommerceCustomer();
        $cust->setName('roman');

        $cart = new ECommerceCart();
        $cart->setPayment('cash');
        $cart->setCustomer($cust);

        $this->_em->persist($cust);
        $this->_em->persist($cart);
        $this->_em->flush();
        $this->_em->clear();

        $result = $this->_em->createQuery('select c, c.name, ca, ca.payment from Doctrine\Tests\Models\ECommerce\ECommerceCart ca join ca.customer c')
            ->getSingleResult(/*\Doctrine\ORM\Query::HYDRATE_ARRAY*/);

        $cart2 = $result[0];
        unset($result[0]);

        $this->assertInstanceOf(ECommerceCart::class, $cart2);
        $this->assertNotInstanceOf(Proxy::class, $cart2->getCustomer());
        $this->assertInstanceOf(ECommerceCustomer::class, $cart2->getCustomer());
        $this->assertEquals(['name' => 'roman', 'payment' => 'cash'], $result);
    }

    /**
     * @group DDC-736
     * @group DDC-925
     * @group DDC-915
     */
    public function testDqlTreeWalkerReordering(): void
    {
        $cust = new ECommerceCustomer();
        $cust->setName('roman');

        $cart = new ECommerceCart();
        $cart->setPayment('cash');
        $cart->setCustomer($cust);

        $this->_em->persist($cust);
        $this->_em->persist($cart);
        $this->_em->flush();
        $this->_em->clear();

        $dql    = 'select c, c.name, ca, ca.payment from Doctrine\Tests\Models\ECommerce\ECommerceCart ca join ca.customer c';
        $result = $this->_em->createQuery($dql)
                            ->setHint(Query::HINT_CUSTOM_TREE_WALKERS, [DisableFetchJoinTreeWalker::class])
                            ->getResult();

        $cart2 = $result[0][0];
        assert($cart2 instanceof ECommerceCart);
        $this->assertInstanceOf(Proxy::class, $cart2->getCustomer());
    }
}

class DisableFetchJoinTreeWalker extends TreeWalkerAdapter
{
    public function walkSelectStatement(AST\SelectStatement $AST): void
    {
        $this->walkSelectClause($AST->selectClause);
    }

    /**
     * {@inheritdoc}
     */
    public function walkSelectClause($selectClause): void
    {
        assert($selectClause instanceof SelectClause);
        foreach ($selectClause->selectExpressions as $key => $selectExpr) {
            assert($selectExpr instanceof SelectExpression);
            if ($selectExpr->expression === 'c') {
                unset($selectClause->selectExpressions[$key]);
                break;
            }
        }
    }
}
