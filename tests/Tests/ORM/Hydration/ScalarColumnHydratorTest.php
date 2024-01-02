<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Hydration;

use Doctrine\ORM\Exception\MultipleSelectorsFoundException;
use Doctrine\ORM\Internal\Hydration\ScalarColumnHydrator;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\Tests\Models\CMS\CmsUser;

use function sprintf;

class ScalarColumnHydratorTest extends HydrationTestCase
{
    /**
     * Select u.id from CmsUser u
     */
    public function testEmptyResultTest(): void
    {
        $rsm = new ResultSetMapping();

        $rsm->addEntityResult(CmsUser::class, 'u');
        $rsm->addFieldResult('u', 'u__id', 'id');

        $stmt     = $this->createResultMock([]);
        $hydrator = new ScalarColumnHydrator($this->entityManager);
        $result   = $hydrator->hydrateAll($stmt, $rsm);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /**
     * Select u.id from CmsUser u
     */
    public function testSingleColumnEntityQueryWithoutScalarMap(): void
    {
        $rsm = new ResultSetMapping();

        $rsm->addEntityResult(CmsUser::class, 'u');
        $rsm->addFieldResult('u', 'u__id', 'id');

        $resultSet = [
            ['u__id' => '1'],
            ['u__id' => '2'],
        ];

        $stmt     = $this->createResultMock($resultSet);
        $hydrator = new ScalarColumnHydrator($this->entityManager);
        $result   = $hydrator->hydrateAll($stmt, $rsm);

        $this->assertIsArray($result);
        $this->assertCount(2, $result);

        $this->assertEquals(1, $result[0]);
        $this->assertEquals(2, $result[1]);
    }

    /**
     * Select u.id from CmsUser u
     */
    public function testSingleColumnEntityQueryWithScalarMap(): void
    {
        $rsm = new ResultSetMapping();

        $rsm->addEntityResult(CmsUser::class, 'u');
        $rsm->addFieldResult('u', 'u__id', 'id');
        $rsm->addScalarResult('sclr0', 'id');
        $rsm->addIndexByScalar('sclr0');

        $resultSet = [
            ['u__id' => '1'],
            ['u__id' => '2'],
        ];

        $stmt     = $this->createResultMock($resultSet);
        $hydrator = new ScalarColumnHydrator($this->entityManager);
        $result   = $hydrator->hydrateAll($stmt, $rsm);

        $this->assertIsArray($result);
        $this->assertCount(2, $result);

        $this->assertEquals(1, $result[0]);
        $this->assertEquals(2, $result[1]);
    }

    /**
     * Select u.id, u.name from CmsUser u
     */
    public function testMultipleColumnEntityQueryThrowsException(): void
    {
        $rsm = new ResultSetMapping();

        $rsm->addEntityResult(CmsUser::class, 'u');
        $rsm->addFieldResult('u', 'u__id', 'id');
        $rsm->addFieldResult('u', 'u__name', 'name');

        $resultSet = [
            [
                'u__id'   => '1',
                'u__name' => 'Gregoire',
            ],
            [
                'u__id'   => '2',
                'u__name' => 'Bhushan',
            ],
        ];

        $stmt     = $this->createResultMock($resultSet);
        $hydrator = new ScalarColumnHydrator($this->entityManager);

        $this->expectException(MultipleSelectorsFoundException::class);
        $this->expectExceptionMessage(sprintf(
            MultipleSelectorsFoundException::MULTIPLE_SELECTORS_FOUND_EXCEPTION,
            'id, name',
        ));

        $hydrator->hydrateAll($stmt, $rsm);
    }
}
