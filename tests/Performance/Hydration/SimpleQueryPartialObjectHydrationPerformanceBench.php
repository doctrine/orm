<?php

declare(strict_types=1);

namespace Doctrine\Performance\Hydration;

use Doctrine\DBAL\Result;
use Doctrine\ORM\Internal\Hydration\ObjectHydrator;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\Performance\EntityManagerFactory;
use Doctrine\Tests\Mocks\ArrayResultFactory;
use Doctrine\Tests\Models\CMS\CmsAddress;
use Doctrine\Tests\Models\CMS\CmsUser;
use PhpBench\Benchmark\Metadata\Annotations\BeforeMethods;

/** @BeforeMethods({"init"}) */
final class SimpleQueryPartialObjectHydrationPerformanceBench
{
    private ObjectHydrator|null $hydrator = null;

    private ResultSetMapping|null $rsm = null;

    private Result|null $result = null;

    public function init(): void
    {
        $resultSet = [
            [
                'u__id'       => '1',
                'u__status'   => 'developer',
                'u__username' => 'romanb',
                'u__name'     => 'Roman',
                'a__id'       => '1',
            ],
            [
                'u__id'       => '1',
                'u__status'   => 'developer',
                'u__username' => 'romanb',
                'u__name'     => 'Roman',
                'a__id'       => '1',
            ],
            [
                'u__id'       => '2',
                'u__status'   => 'developer',
                'u__username' => 'romanb',
                'u__name'     => 'Roman',
                'a__id'       => '1',
            ],
        ];

        for ($i = 4; $i < 10000; ++$i) {
            $resultSet[] = [
                'u__id'       => $i,
                'u__status'   => 'developer',
                'u__username' => 'jwage',
                'u__name'     => 'Jonathan',
                'a__id'       => '1',
            ];
        }

        $this->result   = ArrayResultFactory::createWrapperResultFromArray($resultSet);
        $this->hydrator = new ObjectHydrator(EntityManagerFactory::getEntityManager([]));
        $this->rsm      = new ResultSetMapping();

        $this->rsm->addEntityResult(CmsUser::class, 'u');
        $this->rsm->addFieldResult('u', 'u__id', 'id');
        $this->rsm->addFieldResult('u', 'u__status', 'status');
        $this->rsm->addFieldResult('u', 'u__username', 'username');
        $this->rsm->addFieldResult('u', 'u__name', 'name');
        $this->rsm->addJoinedEntityResult(CmsAddress::class, 'a', 'u', 'address');
        $this->rsm->addFieldResult('a', 'a__id', 'id');
    }

    public function benchHydration(): void
    {
        $this->hydrator->hydrateAll($this->result, $this->rsm, [Query::HINT_FORCE_PARTIAL_LOAD => true]);
    }
}
