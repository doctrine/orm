<?php

namespace Doctrine\Tests\ORM\Hydration;

use Doctrine\ORM\Internal\Hydration\SingleScalarHydrator;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\Tests\Mocks\HydratorMockStatement;
use Doctrine\Tests\Models\CMS\CmsUser;

class SingleScalarHydratorTest extends HydrationTestCase
{
    public static function validResultSetProvider()
    {
        // SELECT u.name FROM CmsUser u WHERE u.id = 1
        yield [
            [
                ['u__name' => 'romanb'],
            ],
            'romanb',
        ];
        // SELECT u.id FROM CmsUser u WHERE u.id = 1
        yield [
            [
                ['u__id' => '1'],
            ],
            1,
        ];
        // SELECT
        //   u.id,
        //   COUNT(u.postsCount + u.likesCount) AS HIDDEN score
        // FROM CmsUser u
        // WHERE u.id = 1
        yield [
            [
                [
                    'u__id' => '1',
                    'score' => 10, // Ignored since not part of ResultSetMapping (cf. HIDDEN keyword)
                ],
            ],
            1,
        ];
    }

    /**
     * @dataProvider validResultSetProvider
     */
    public function testHydrateSingleScalarFromFieldMappingWithValidResultSet(array $resultSet, $expectedResult)
    {
        $rsm = new ResultSetMapping();
        $rsm->addEntityResult(CmsUser::class, 'u');
        $rsm->addFieldResult('u', 'u__id', 'id');
        $rsm->addFieldResult('u', 'u__name', 'name');

        $stmt     = new HydratorMockStatement($resultSet);
        $hydrator = new SingleScalarHydrator($this->_em);

        $result = $hydrator->hydrateAll($stmt, $rsm);
        $this->assertEquals($expectedResult, $result);
    }

    /**
     * @dataProvider validResultSetProvider
     */
    public function testHydrateSingleScalarFromScalarMappingWithValidResultSet(array $resultSet, $expectedResult)
    {
        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('u__id', 'id', 'string');
        $rsm->addScalarResult('u__name', 'name', 'string');

        $stmt     = new HydratorMockStatement($resultSet);
        $hydrator = new SingleScalarHydrator($this->_em);

        $result = $hydrator->hydrateAll($stmt, $rsm);
        $this->assertEquals($expectedResult, $result);
    }

    public static function invalidResultSetProvider()
    {
        // Single row (OK), multiple columns (NOT OK)
        yield [
            [
                [
                    'u__id'   => '1',
                    'u__name' => 'romanb',
                ],
            ],
        ];
        // Multiple rows (NOT OK), single column (OK)
        yield [
            [
                ['u__id' => '1'],
                ['u__id' => '2'],
            ],
        ];
        // Multiple rows (NOT OK), single column with HIDDEN result (OK)
        yield [
            [
                [
                    'u__id' => '1',
                    'score' => 10, // Ignored since not part of ResultSetMapping
                ],
                [
                    'u__id' => '2',
                    'score' => 10, // Ignored since not part of ResultSetMapping
                ],
            ],
            1,
        ];
        // Multiple row (NOT OK), multiple columns (NOT OK)
        yield [
            [
                [
                    'u__id'   => '1',
                    'u__name' => 'romanb',
                ],
                [
                    'u__id'   => '2',
                    'u__name' => 'romanb',
                ],
            ],
        ];
    }

    /**
     * @dataProvider invalidResultSetProvider
     */
    public function testHydrateSingleScalarFromFieldMappingWithInvalidResultSet(array $resultSet)
    {
        $rsm = new ResultSetMapping();
        $rsm->addEntityResult(CmsUser::class, 'u');
        $rsm->addFieldResult('u', 'u__id', 'id');
        $rsm->addFieldResult('u', 'u__name', 'name');

        $stmt     = new HydratorMockStatement($resultSet);
        $hydrator = new SingleScalarHydrator($this->_em);

        $this->expectException(NonUniqueResultException::class);
        $hydrator->hydrateAll($stmt, $rsm);
    }

    /**
     * @dataProvider invalidResultSetProvider
     */
    public function testHydrateSingleScalarFromScalarMappingWithInvalidResultSet(array $resultSet)
    {
        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('u__id', 'id', 'string');
        $rsm->addScalarResult('u__name', 'name', 'string');

        $stmt     = new HydratorMockStatement($resultSet);
        $hydrator = new SingleScalarHydrator($this->_em);

        $this->expectException(NonUniqueResultException::class);
        $hydrator->hydrateAll($stmt, $rsm);
    }
}
