<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Hydration;

use Doctrine\ORM\Internal\Hydration\SingleScalarHydrator;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\Tests\Mocks\HydratorMockStatement;
use Doctrine\Tests\Models\CMS\CmsUser;

use function in_array;

class SingleScalarHydratorTest extends HydrationTestCase
{
    /** Result set provider for the HYDRATE_SINGLE_SCALAR tests */
    public static function singleScalarResultSetProvider(): array
    {
        return [
            // valid
            'valid' => [
                'name'      => 'result1',
                'resultSet' => [
                    ['u__name' => 'romanb'],
                ],
            ],
            // valid
            [
                'name'      => 'result2',
                'resultSet' => [
                    ['u__id' => '1'],
                ],
            ],
            // invalid
            [
                'name'      => 'result3',
                'resultSet' => [
                    [
                        'u__id'   => '1',
                        'u__name' => 'romanb',
                    ],
                ],
            ],
            // invalid
            [
                'name'      => 'result4',
                'resultSet' => [
                    ['u__id' => '1'],
                    ['u__id' => '2'],
                ],
            ],
        ];
    }

    /**
     * select u.name from CmsUser u where u.id = 1
     *
     * @dataProvider singleScalarResultSetProvider
     */
    public function testHydrateSingleScalar($name, $resultSet): void
    {
        $rsm = new ResultSetMapping();
        $rsm->addEntityResult(CmsUser::class, 'u');
        $rsm->addFieldResult('u', 'u__id', 'id');
        $rsm->addFieldResult('u', 'u__name', 'name');

        $stmt     = new HydratorMockStatement($resultSet);
        $hydrator = new SingleScalarHydrator($this->entityManager);

        if ($name === 'result1') {
            $result = $hydrator->hydrateAll($stmt, $rsm);
            $this->assertEquals('romanb', $result);

            return;
        }

        if ($name === 'result2') {
            $result = $hydrator->hydrateAll($stmt, $rsm);
            $this->assertEquals(1, $result);

            return;
        }

        if (in_array($name, ['result3', 'result4'], true)) {
            $this->expectException(NonUniqueResultException::class);
            $hydrator->hydrateAll($stmt, $rsm);
        }
    }
}
