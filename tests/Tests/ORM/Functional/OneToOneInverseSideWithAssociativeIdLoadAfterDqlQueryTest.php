<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Tests\Models\OneToOneInverseSideWithAssociativeIdLoad\InverseSide;
use Doctrine\Tests\Models\OneToOneInverseSideWithAssociativeIdLoad\InverseSideIdTarget;
use Doctrine\Tests\Models\OneToOneInverseSideWithAssociativeIdLoad\OwningSide;
use Doctrine\Tests\OrmFunctionalTestCase;
use PHPUnit\Framework\Attributes\Group;

use function assert;

class OneToOneInverseSideWithAssociativeIdLoadAfterDqlQueryTest extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchemaForModels(OwningSide::class, InverseSideIdTarget::class, InverseSide::class);
    }

    #[Group('GH-11108')]
    public function testInverseSideWithAssociativeIdOneToOneLoadedAfterDqlQuery(): void
    {
        $owner     = new OwningSide();
        $inverseId = new InverseSideIdTarget();
        $inverse   = new InverseSide();

        $owner->id              = 'owner';
        $inverseId->id          = 'inverseId';
        $inverseId->inverseSide = $inverse;
        $inverse->associativeId = $inverseId;
        $owner->inverse         = $inverse;
        $inverse->owning        = $owner;

        $this->_em->persist($owner);
        $this->_em->persist($inverseId);
        $this->_em->persist($inverse);
        $this->_em->flush();
        $this->_em->clear();

        $fetchedInverse = $this
            ->_em
            ->createQueryBuilder()
            ->select('inverse')
            ->from(InverseSide::class, 'inverse')
            ->andWhere('inverse.associativeId = :associativeId')
            ->setParameter('associativeId', 'inverseId')
            ->getQuery()
            ->getSingleResult();
        assert($fetchedInverse instanceof InverseSide);

        self::assertInstanceOf(InverseSide::class, $fetchedInverse);
        self::assertInstanceOf(InverseSideIdTarget::class, $fetchedInverse->associativeId);
        self::assertInstanceOf(OwningSide::class, $fetchedInverse->owning);

        $this->assertSQLEquals(
            'select o0_.associativeid as associativeid_0 from one_to_one_inverse_side_assoc_id_load_inverse o0_ where o0_.associativeid = ?',
            $this->getLastLoggedQuery(1)['sql'],
        );

        $this->assertSQLEquals(
            'select t0.id as id_1, t0.inverse as inverse_2 from one_to_one_inverse_side_assoc_id_load_owning t0 where t0.inverse = ?',
            $this->getLastLoggedQuery()['sql'],
        );
    }
}
