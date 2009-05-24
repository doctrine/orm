<?php

namespace Doctrine\Tests\ORM\Performance;

require_once __DIR__ . '/../../TestInit.php';

use Doctrine\Tests\Mocks\HydratorMockStatement;
use Doctrine\ORM\Query\ResultSetMapping;

/**
 * Tests to prevent serious performance regressions.
 *
 * IMPORTANT: Be sure to run these tests withoug xdebug or similar tools that
 * seriously degrade performance.
 *
 * @author robo
 */
class HydrationPerformanceTest extends \Doctrine\Tests\OrmPerformanceTestCase
{
    /**
     * Times for comparison:
     *
     * [romanb: 10000 rows => 1.8 seconds]
     *
     * MAXIMUM TIME: 3 seconds
     */
    public function testNewHydrationSimpleQueryArrayHydrationPerformance()
    {
        $rsm = new ResultSetMapping;
        $rsm->addEntityResult('Doctrine\Tests\Models\CMS\CmsUser', 'u');
        $rsm->addFieldResult('u', 'u__id', 'id');
        $rsm->addFieldResult('u', 'u__status', 'status');
        $rsm->addFieldResult('u', 'u__username', 'username');
        $rsm->addFieldResult('u', 'u__name', 'name');

        // Faked result set
        $resultSet = array(
            //row1
            array(
                'u__id' => '1',
                'u__status' => 'developer',
                'u__username' => 'romanb',
                'u__name' => 'Roman',
            ),
            array(
                'u__id' => '1',
                'u__status' => 'developer',
                'u__username' => 'romanb',
                'u__name' => 'Roman',
            ),
            array(
                'u__id' => '2',
                'u__status' => 'developer',
                'u__username' => 'romanb',
                'u__name' => 'Roman',
            )
        );

        for ($i = 4; $i < 10000; ++$i) {
            $resultSet[] = array(
                'u__id' => $i,
                'u__status' => 'developer',
                'u__username' => 'jwage',
                'u__name' => 'Jonathan',
            );
        }

        $stmt = new HydratorMockStatement($resultSet);
        $hydrator = new \Doctrine\ORM\Internal\Hydration\ArrayHydrator($this->_em);

        $this->setMaxRunningTime(3);
        $result = $hydrator->hydrateAll($stmt, $rsm);
    }

    /**
     * Times for comparison:
     *
     * [romanb: 10000 rows => 3.0 seconds]
     *
     * MAXIMUM TIME: 4 seconds
     */
    public function testNewHydrationMixedQueryFetchJoinArrayHydrationPerformance()
    {
        $rsm = new ResultSetMapping;
        $rsm->addEntityResult('Doctrine\Tests\Models\CMS\CmsUser', 'u');
        $rsm->addJoinedEntityResult(
                'Doctrine\Tests\Models\CMS\CmsPhonenumber',
                'p',
                'u',
                $this->_em->getClassMetadata('Doctrine\Tests\Models\CMS\CmsUser')->getAssociationMapping('phonenumbers')
        );
        $rsm->addFieldResult('u', 'u__id', 'id');
        $rsm->addFieldResult('u', 'u__status', 'status');
        $rsm->addFieldResult('u', 'u__username', 'username');
        $rsm->addFieldResult('u', 'u__name', 'name');
        $rsm->addScalarResult('sclr0', 'nameUpper');
        $rsm->addFieldResult('p', 'p__phonenumber', 'phonenumber');

        // Faked result set
        $resultSet = array(
            //row1
            array(
                'u__id' => '1',
                'u__status' => 'developer',
                'u__username' => 'romanb',
                'u__name' => 'Roman',
                'sclr0' => 'ROMANB',
                'p__phonenumber' => '42',
            ),
            array(
                'u__id' => '1',
                'u__status' => 'developer',
                'u__username' => 'romanb',
                'u__name' => 'Roman',
                'sclr0' => 'ROMANB',
                'p__phonenumber' => '43',
            ),
            array(
                'u__id' => '2',
                'u__status' => 'developer',
                'u__username' => 'romanb',
                'u__name' => 'Roman',
                'sclr0' => 'JWAGE',
                'p__phonenumber' => '91'
            )
        );

        for ($i = 4; $i < 10000; ++$i) {
            $resultSet[] = array(
                'u__id' => $i,
                'u__status' => 'developer',
                'u__username' => 'jwage',
                'u__name' => 'Jonathan',
                'sclr0' => 'JWAGE' . $i,
                'p__phonenumber' => '91'
            );
        }

        $stmt = new HydratorMockStatement($resultSet);
        $hydrator = new \Doctrine\ORM\Internal\Hydration\ArrayHydrator($this->_em);

        $this->setMaxRunningTime(4);
        $result = $hydrator->hydrateAll($stmt, $rsm);
    }

    /**
     * [romanb: 10000 rows => 3.8 seconds]
     *
     * MAXIMUM TIME: 5 seconds
     */
    public function testSimpleQueryObjectHydrationPerformance()
    {
        $rsm = new ResultSetMapping;
        $rsm->addEntityResult('Doctrine\Tests\Models\CMS\CmsUser', 'u');
        $rsm->addFieldResult('u', 'u__id', 'id');
        $rsm->addFieldResult('u', 'u__status', 'status');
        $rsm->addFieldResult('u', 'u__username', 'username');
        $rsm->addFieldResult('u', 'u__name', 'name');

        // Faked result set
        $resultSet = array(
            //row1
            array(
                'u__id' => '1',
                'u__status' => 'developer',
                'u__username' => 'romanb',
                'u__name' => 'Roman',
            ),
            array(
                'u__id' => '1',
                'u__status' => 'developer',
                'u__username' => 'romanb',
                'u__name' => 'Roman',
            ),
            array(
                'u__id' => '2',
                'u__status' => 'developer',
                'u__username' => 'romanb',
                'u__name' => 'Roman',
            )
        );

        for ($i = 4; $i < 10000; ++$i) {
            $resultSet[] = array(
                'u__id' => $i,
                'u__status' => 'developer',
                'u__username' => 'jwage',
                'u__name' => 'Jonathan',
            );
        }

        $stmt = new HydratorMockStatement($resultSet);
        $hydrator = new \Doctrine\ORM\Internal\Hydration\ObjectHydrator($this->_em);

        $this->setMaxRunningTime(5);
        $result = $hydrator->hydrateAll($stmt, $rsm);
        echo count($result);
    }

    /**
     * [romanb: 2000 rows => 3.1 seconds]
     *
     * MAXIMUM TIME: 4 seconds
     */
    public function testMixedQueryFetchJoinObjectHydrationPerformance()
    {
        $rsm = new ResultSetMapping;
        $rsm->addEntityResult('Doctrine\Tests\Models\CMS\CmsUser', 'u');
        $rsm->addJoinedEntityResult(
                'Doctrine\Tests\Models\CMS\CmsPhonenumber',
                'p',
                'u',
                $this->_em->getClassMetadata('Doctrine\Tests\Models\CMS\CmsUser')->getAssociationMapping('phonenumbers')
        );
        $rsm->addFieldResult('u', 'u__id', 'id');
        $rsm->addFieldResult('u', 'u__status', 'status');
        $rsm->addFieldResult('u', 'u__username', 'username');
        $rsm->addFieldResult('u', 'u__name', 'name');
        $rsm->addScalarResult('sclr0', 'nameUpper');
        $rsm->addFieldResult('p', 'p__phonenumber', 'phonenumber');

        // Faked result set
        $resultSet = array(
            //row1
            array(
                'u__id' => '1',
                'u__status' => 'developer',
                'u__username' => 'romanb',
                'u__name' => 'Roman',
                'sclr0' => 'ROMANB',
                'p__phonenumber' => '42',
            ),
            array(
                'u__id' => '1',
                'u__status' => 'developer',
                'u__username' => 'romanb',
                'u__name' => 'Roman',
                'sclr0' => 'ROMANB',
                'p__phonenumber' => '43',
            ),
            array(
                'u__id' => '2',
                'u__status' => 'developer',
                'u__username' => 'romanb',
                'u__name' => 'Roman',
                'sclr0' => 'JWAGE',
                'p__phonenumber' => '91'
            )
        );

        for ($i = 4; $i < 2000; ++$i) {
            $resultSet[] = array(
                'u__id' => $i,
                'u__status' => 'developer',
                'u__username' => 'jwage',
                'u__name' => 'Jonathan',
                'sclr0' => 'JWAGE' . $i,
                'p__phonenumber' => '91'
            );
        }

        $stmt = new HydratorMockStatement($resultSet);
        $hydrator = new \Doctrine\ORM\Internal\Hydration\ObjectHydrator($this->_em);

        $this->setMaxRunningTime(4);
        $result = $hydrator->hydrateAll($stmt, $rsm);
    }
}

