<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Hydration;

use Doctrine\ORM\Internal\Hydration\SingleScalarHydrator;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\Tests\Models\CMS\CmsUser;
use Generator;
use PHPUnit\Framework\Attributes\DataProvider;

class SingleScalarHydratorTest extends HydrationTestCase
{
    /** @return Generator<int, array{list<array<string,mixed>>,mixed}> */
    public static function validResultSetProvider(): Generator
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

    /** @param list<array<string, mixed>> $resultSet */
    #[DataProvider('validResultSetProvider')]
    public function testHydrateSingleScalarFromFieldMappingWithValidResultSet(array $resultSet, mixed $expectedResult): void
    {
        $rsm = new ResultSetMapping();
        $rsm->addEntityResult(CmsUser::class, 'u');
        $rsm->addFieldResult('u', 'u__id', 'id');
        $rsm->addFieldResult('u', 'u__name', 'name');

        $stmt     = $this->createResultMock($resultSet);
        $hydrator = new SingleScalarHydrator($this->entityManager);

        $result = $hydrator->hydrateAll($stmt, $rsm);
        $this->assertEquals($expectedResult, $result);
    }

    /** @param list<array<string, mixed>> $resultSet */
    #[DataProvider('validResultSetProvider')]
    public function testHydrateSingleScalarFromScalarMappingWithValidResultSet(array $resultSet, mixed $expectedResult): void
    {
        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('u__id', 'id', 'string');
        $rsm->addScalarResult('u__name', 'name', 'string');

        $stmt     = $this->createResultMock($resultSet);
        $hydrator = new SingleScalarHydrator($this->entityManager);

        $result = $hydrator->hydrateAll($stmt, $rsm);
        $this->assertEquals($expectedResult, $result);
    }

    /** @return Generator<int, array{list<array<string,mixed>>}> */
    public static function invalidResultSetProvider(): Generator
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

    /** @param list<array<string, mixed>> $resultSet */
    #[DataProvider('invalidResultSetProvider')]
    public function testHydrateSingleScalarFromFieldMappingWithInvalidResultSet(array $resultSet): void
    {
        $rsm = new ResultSetMapping();
        $rsm->addEntityResult(CmsUser::class, 'u');
        $rsm->addFieldResult('u', 'u__id', 'id');
        $rsm->addFieldResult('u', 'u__name', 'name');

        $stmt     = $this->createResultMock($resultSet);
        $hydrator = new SingleScalarHydrator($this->entityManager);

        $this->expectException(NonUniqueResultException::class);
        $hydrator->hydrateAll($stmt, $rsm);
    }

    /** @param list<array<string, mixed>> $resultSet */
    #[DataProvider('invalidResultSetProvider')]
    public function testHydrateSingleScalarFromScalarMappingWithInvalidResultSet(array $resultSet): void
    {
        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('u__id', 'id', 'string');
        $rsm->addScalarResult('u__name', 'name', 'string');

        $stmt     = $this->createResultMock($resultSet);
        $hydrator = new SingleScalarHydrator($this->entityManager);

        $this->expectException(NonUniqueResultException::class);
        $hydrator->hydrateAll($stmt, $rsm);
    }
}
