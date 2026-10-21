<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Hydration;

use Doctrine\ORM\Internal\Hydration\ScalarHydrator;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\Tests\Models\CMS\CmsUser;
use PHPUnit\Framework\Attributes\Group;

use function iterator_to_array;

class ScalarHydratorTest extends HydrationTestCase
{
    public function testColumnPlanIsResetBetweenHydrationsAndIteration(): void
    {
        $hydrator = new ScalarHydrator($this->entityManager);
        $rsm      = new ResultSetMapping();
        $rsm->addEntityResult(CmsUser::class, 'u');
        $rsm->addFieldResult('u', 'value', 'id');

        self::assertSame([['u_id' => 12]], $hydrator->hydrateAll(
            $this->createResultMock([['value' => '12', 'ignored' => 'unused']]),
            $rsm,
        ));

        $scalars = new ResultSetMapping();
        $scalars->addScalarResult('value', 'number', 'integer');
        self::assertSame([['number' => '12']], $hydrator->hydrateAll(
            $this->createResultMock([['value' => '12']]),
            $scalars,
        ));

        self::assertSame([['u_id' => 13], ['u_id' => null]], iterator_to_array($hydrator->toIterable(
            $this->createResultMock([['value' => '13'], ['value' => null]]),
            $rsm,
        )));
    }

    /**
     * Select u.id, u.name from CmsUser u
     */
    public function testNewHydrationSimpleEntityQuery(): void
    {
        $rsm = new ResultSetMapping();
        $rsm->addEntityResult(CmsUser::class, 'u');
        $rsm->addFieldResult('u', 'u__id', 'id');
        $rsm->addFieldResult('u', 'u__name', 'name');

        // Faked result set
        $resultSet = [
            [
                'u__id' => '1',
                'u__name' => 'romanb',
            ],
            [
                'u__id' => '2',
                'u__name' => 'jwage',
            ],
        ];

        $stmt     = $this->createResultMock($resultSet);
        $hydrator = new ScalarHydrator($this->entityManager);

        $result = $hydrator->hydrateAll($stmt, $rsm);

        self::assertIsArray($result);
        self::assertCount(2, $result);
        self::assertEquals('romanb', $result[0]['u_name']);
        self::assertEquals(1, $result[0]['u_id']);
        self::assertEquals('jwage', $result[1]['u_name']);
        self::assertEquals(2, $result[1]['u_id']);
    }

    #[Group('DDC-407')]
    public function testHydrateScalarResults(): void
    {
        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('foo1', 'foo', 'string');
        $rsm->addScalarResult('bar2', 'bar', 'string');
        $rsm->addScalarResult('baz3', 'baz', 'string');

        $resultSet = [
            [
                'foo1' => 'A',
                'bar2' => 'B',
                'baz3' => 'C',
            ],
        ];

        $stmt     = $this->createResultMock($resultSet);
        $hydrator = new ScalarHydrator($this->entityManager);

        self::assertCount(1, $hydrator->hydrateAll($stmt, $rsm));
    }

    #[Group('DDC-644')]
    public function testSkipUnknownColumns(): void
    {
        $rsm = new ResultSetMapping();
        $rsm->addEntityResult(CmsUser::class, 'u');
        $rsm->addFieldResult('u', 'u__id', 'id');
        $rsm->addFieldResult('u', 'u__name', 'name');
        $rsm->addScalarResult('foo1', 'foo', 'string');
        $rsm->addScalarResult('bar2', 'bar', 'string');
        $rsm->addScalarResult('baz3', 'baz', 'string');

        $resultSet = [
            [
                'u__id' => '1',
                'u__name' => 'romanb',
                'foo1' => 'A',
                'bar2' => 'B',
                'baz3' => 'C',
                'foo' => 'bar', // Unknown!
            ],
        ];

        $stmt     = $this->createResultMock($resultSet);
        $hydrator = new ScalarHydrator($this->entityManager);

        self::assertCount(1, $hydrator->hydrateAll($stmt, $rsm));
    }
}
