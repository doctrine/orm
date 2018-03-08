<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\ORM\Tools\ToolsException;
use Doctrine\Tests\Models\OneToOneInverseSideLoad\InverseSide;
use Doctrine\Tests\Models\OneToOneInverseSideLoad\OwningSide;
use Doctrine\Tests\OrmFunctionalTestCase;

class OneToOneInverseSideLoadAfterDqlQueryTest extends OrmFunctionalTestCase
{
    protected function setUp() : void
    {
        parent::setUp();

        try {
            $this->schemaTool->createSchema([
                $this->em->getClassMetadata(OwningSide::class),
                $this->em->getClassMetadata(InverseSide::class),
            ]);
        } catch (ToolsException $e) {
            // ignored
        }
    }

    /**
     * @group 6759
     */
    public function testInverseSideOneToOneLoadedAfterDqlQuery() : void
    {
        $owner   = new OwningSide();
        $inverse = new InverseSide();

        $owner->id       = 'owner';
        $inverse->id     = 'inverse';
        $owner->inverse  = $inverse;
        $inverse->owning = $owner;

        $this->em->persist($owner);
        $this->em->persist($inverse);
        $this->em->flush();
        $this->em->clear();

        /* @var $fetchedInverse InverseSide */
        $fetchedInverse = $this->em->createQueryBuilder()
                                   ->select('inverse')
                                   ->from(InverseSide::class, 'inverse')
                                   ->andWhere('inverse.id = :id')
                                   ->setParameter('id', 'inverse')
                                   ->getQuery()
                                   ->getSingleResult();

        self::assertInstanceOf(InverseSide::class, $fetchedInverse);
        self::assertInstanceOf(OwningSide::class, $fetchedInverse->owning);

        self::assertSQLEquals(
            'select t0."id" as c0 from "one_to_one_inverse_side_load_inverse" t0 where t0."id" = ?',
            $this->sqlLoggerStack->queries[$this->sqlLoggerStack->currentQuery - 1]['sql']
        );

        self::assertSQLEquals(
            'select t0."id" as c1, t0."inverse" as c2 from "one_to_one_inverse_side_load_owning" t0 WHERE t0."inverse" = ?',
            $this->sqlLoggerStack->queries[$this->sqlLoggerStack->currentQuery]['sql']
        );
    }
}
