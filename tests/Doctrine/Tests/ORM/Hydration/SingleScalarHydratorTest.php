<?php

namespace Doctrine\Tests\ORM\Hydration;

use Doctrine\Common\Cache\ArrayCache;
use Doctrine\DBAL\Cache\ResultCacheStatement;
use Doctrine\ORM\Internal\Hydration\SingleScalarHydrator;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Tests\Mocks\HydratorMockStatement;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\Tests\Models\CMS\CmsUser;

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
                    [
                        'u__name' => 'romanb',
                    ],
                ],
            ],
            // valid
            [
                'name'      => 'result2',
                'resultSet' => [
                    [
                        'u__id' => '1',
                    ],
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
                    [
                        'u__id' => '1',
                    ],
                    [
                        'u__id' => '2',
                    ],
                ],
            ],
        ];
    }

    /**
     * select u.name from CmsUser u where u.id = 1
     *
     * @dataProvider singleScalarResultSetProvider
     */
    public function testHydrateSingleScalar($name, $resultSet)
    {
        $rsm = new ResultSetMapping;
        $rsm->addEntityResult(CmsUser::class, 'u');
        $rsm->addFieldResult('u', 'u__id', 'id');
        $rsm->addFieldResult('u', 'u__name', 'name');

        $stmt = new HydratorMockStatement($resultSet);
        $hydrator = new SingleScalarHydrator($this->_em);

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

    public function testHydrateSingleScalarResultCreatesCache()
    {
        $rsm = new ResultSetMapping;
        $rsm->addEntityResult(CmsUser::class, 'u');
        $rsm->addFieldResult('u', 'u__id', 'id');
        $rsm->addFieldResult('u', 'u__name', 'name');

        $cache    = new ArrayCache();
        $rows     = [['u__name' => 'romanb']];
        $stmt     = new HydratorMockStatement($rows);

        $stmt     = new ResultCacheStatement($stmt, $cache, 'foo', 'bar', 30);
        $hydrator = new SingleScalarHydrator($this->_em);

        $result = $hydrator->hydrateAll($stmt, $rsm);
        $this->assertEquals('romanb', $result);
        $this->assertEquals(['bar' => $rows], $cache->fetch('foo'));
    }
}
