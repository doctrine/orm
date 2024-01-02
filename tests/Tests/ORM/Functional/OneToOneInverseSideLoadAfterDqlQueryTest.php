<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Tests\Models\OneToOneInverseSideLoad\InverseSide;
use Doctrine\Tests\Models\OneToOneInverseSideLoad\OwningSide;
use Doctrine\Tests\OrmFunctionalTestCase;

use function assert;

class OneToOneInverseSideLoadAfterDqlQueryTest extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchemaForModels(OwningSide::class, InverseSide::class);
    }

    /** @group GH-6759 */
    public function testInverseSideOneToOneLoadedAfterDqlQuery(): void
    {
        $owner   = new OwningSide();
        $inverse = new InverseSide();

        $owner->id       = 'owner';
        $inverse->id     = 'inverse';
        $owner->inverse  = $inverse;
        $inverse->owning = $owner;

        $this->_em->persist($owner);
        $this->_em->persist($inverse);
        $this->_em->flush();
        $this->_em->clear();

        $fetchedInverse = $this
            ->_em
            ->createQueryBuilder()
            ->select('inverse')
            ->from(InverseSide::class, 'inverse')
            ->andWhere('inverse.id = :id')
            ->setParameter('id', 'inverse')
            ->getQuery()
            ->getSingleResult();
        assert($fetchedInverse instanceof InverseSide);

        self::assertInstanceOf(InverseSide::class, $fetchedInverse);
        self::assertInstanceOf(OwningSide::class, $fetchedInverse->owning);

        $this->assertSQLEquals(
            'select o0_.id as id_0 from one_to_one_inverse_side_load_inverse o0_ where o0_.id = ?',
            $this->getLastLoggedQuery(1)['sql']
        );

        $this->assertSQLEquals(
            'select t0.id as id_1, t0.inverse as inverse_2 from one_to_one_inverse_side_load_owning t0 WHERE t0.inverse = ?',
            $this->getLastLoggedQuery()['sql']
        );
    }
}
