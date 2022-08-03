<?php

declare(strict_types=1);

namespace Doctrine\Performance\Hydration;

use Doctrine\ORM\Internal\Hydration\ArrayHydrator;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\Performance\EntityManagerFactory;
use Doctrine\Tests\Mocks\HydratorMockStatement;
use Doctrine\Tests\Models\CMS\CmsUser;
use PhpBench\Benchmark\Metadata\Annotations\BeforeMethods;

/**
 * @BeforeMethods({"init"})
 */
final class SimpleQueryArrayHydrationPerformanceBench
{
    /** @var ArrayHydrator */
    private $hydrator;

    /** @var ResultSetMapping */
    private $rsm;

    /** @var HydratorMockStatement */
    private $stmt;

    public function init(): void
    {
        $resultSet = [
            [
                'u__id'       => '1',
                'u__status'   => 'developer',
                'u__username' => 'romanb',
                'u__name'     => 'Roman',
            ],
            [
                'u__id'       => '1',
                'u__status'   => 'developer',
                'u__username' => 'romanb',
                'u__name'     => 'Roman',
            ],
            [
                'u__id'       => '2',
                'u__status'   => 'developer',
                'u__username' => 'romanb',
                'u__name'     => 'Roman',
            ],
        ];

        for ($i = 4; $i < 10000; ++$i) {
            $resultSet[] = [
                'u__id'       => $i,
                'u__status'   => 'developer',
                'u__username' => 'jwage',
                'u__name'     => 'Jonathan',
            ];
        }

        $this->stmt     = new HydratorMockStatement($resultSet);
        $this->hydrator = new ArrayHydrator(EntityManagerFactory::getEntityManager([]));
        $this->rsm      = new ResultSetMapping();

        $this->rsm->addEntityResult(CmsUser::class, 'u');
        $this->rsm->addFieldResult('u', 'u__id', 'id');
        $this->rsm->addFieldResult('u', 'u__status', 'status');
        $this->rsm->addFieldResult('u', 'u__username', 'username');
        $this->rsm->addFieldResult('u', 'u__name', 'name');
    }

    public function benchHydration(): void
    {
        $this->hydrator->hydrateAll($this->stmt, $this->rsm);
    }
}
