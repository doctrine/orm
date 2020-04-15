<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Tests\OrmFunctionalTestCase;

/**
 * @group GH-8106
 */
class GH8106Test extends OrmFunctionalTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function setUp() : void
    {
        parent::setUp();

        $this->_schemaTool->createSchema(
            [
                $this->_em->getClassMetadata(GH8106User::class),
            ]
        );
    }

    public function testIssue() : void
    {
        $user = new GH8106User();
        $this->_em->persist($user);
        $this->_em->flush();
        $this->_em->clear();

        $qb = $this->_em->createQueryBuilder();
        $qb
            ->select('u')
            ->from(GH8106User::class, 'u')
            ->where('u.id = :id')
            ->setParameter(':id', 1)
            ->setParameter(':id', 1);

        $result = $qb->getQuery()->getResult(); // should not throw QueryException

        self::assertCount(1, $result);
    }
}

/** @Entity */
class GH8106User
{
    /** @Id @Column(type="integer") @GeneratedValue */
    public $id;
}
