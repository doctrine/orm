<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Tools\Pagination\Paginator;

/**
 * @group issue-6189
 */
class Issue6189Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->_schemaTool->createSchema([
            $this->_em->getClassMetadata(Issue6189Product::CLASSNAME),
            $this->_em->getClassMetadata(Issue6189ProductA::CLASSNAME),
            $this->_em->getClassMetadata(Issue6189ProductB::CLASSNAME),
        ]);
    }

    public function testSimpleArrayTypeHydratedCorrectlyInJoinedInheritance()
    {
        $productA = new Issue6189ProductA();

        $this->_em->persist($productA);
        $this->_em->persist(new Issue6189ProductB());

        $this->_em->flush();

        $qb = $this
            ->_em
            ->createQueryBuilder();

        $paginator = new Paginator(
            $qb
                ->select('p')
                ->from(Issue6189Product::CLASSNAME, 'p')
                ->where($qb->expr()->isInstanceOf('p', ':productType'))
                ->setParameter('productType', $this->_em->getClassMetadata(Issue6189ProductA::CLASSNAME))
                ->setMaxResults(10)
        );

        self::assertCount(1, $paginator);
        self::assertSame([$productA], iterator_to_array($paginator));
    }
}

/**
 * @Entity
 * @InheritanceType("JOINED")
 * @DiscriminatorColumn(name="discr", type="string")
 * @DiscriminatorMap({
 *      "productA" = Issue6189ProductA::class,
 *      "productB" = Issue6189ProductB::class,
 * })
 */
abstract class Issue6189Product
{
    const CLASSNAME = __CLASS__;

    /**
     * @var int
     *
     * @Column(type="integer")
     * @GeneratedValue(strategy="AUTO")
     * @Id
     */
    public $id;
}

/**
 * @Entity
 */
class Issue6189ProductA extends Issue6189Product
{
    const CLASSNAME = __CLASS__;
}

/**
 * @Entity
 */
class Issue6189ProductB extends Issue6189Product
{
    const CLASSNAME = __CLASS__;
}
