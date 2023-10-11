<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Query;
use Doctrine\ORM\Query\AST\SelectExpression;
use Doctrine\ORM\Query\AST\SelectStatement;
use Doctrine\ORM\Query\TreeWalkerAdapter;
use Doctrine\Tests\Models\ECommerce\ECommerceCart;
use Doctrine\Tests\Models\ECommerce\ECommerceCustomer;
use Doctrine\Tests\OrmFunctionalTestCase;
use PHPUnit\Framework\Attributes\Group;

use function assert;

class DDC736Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        $this->useModelSet('ecommerce');

        parent::setUp();
    }

    #[Group('DDC-736')]
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

        self::assertInstanceOf(ECommerceCart::class, $cart2);
        self::assertFalse($this->isUninitializedObject($cart2->getCustomer()));
        self::assertInstanceOf(ECommerceCustomer::class, $cart2->getCustomer());
        self::assertEquals(['name' => 'roman', 'payment' => 'cash'], $result);
    }

    #[Group('DDC-736')]
    #[Group('DDC-925')]
    #[Group('DDC-915')]
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
        self::assertTrue($this->isUninitializedObject($cart2->getCustomer()));
    }
}

class DisableFetchJoinTreeWalker extends TreeWalkerAdapter
{
    public function walkSelectStatement(SelectStatement $selectStatement): void
    {
        foreach ($selectStatement->selectClause->selectExpressions as $key => $selectExpr) {
            assert($selectExpr instanceof SelectExpression);
            if ($selectExpr->expression === 'c') {
                unset($selectStatement->selectClause->selectExpressions[$key]);
                break;
            }
        }
    }
}
