<?php

namespace Doctrine\Tests\ORM\Performance;

require_once __DIR__ . '/../../TestInit.php';

use Doctrine\Tests\Mocks\HydratorMockStatement,
    Doctrine\ORM\Query\ResultSetMapping,
    Doctrine\ORM\Query;

/**
 * Tests to prevent serious performance regressions.
 *
 * IMPORTANT: Be sure to run these tests without xdebug or similar tools that
 * seriously degrade performance.
 *
 * @author robo
 * @group performance
 */
class HydrationPerformanceTest extends \Doctrine\Tests\OrmPerformanceTestCase
{
    /**
     * Times for comparison:
     *
     * [romanb: 10000 rows => 0.7 seconds]
     *
     * MAXIMUM TIME: 1 second
     */
    public function testSimpleQueryScalarHydrationPerformance10000Rows()
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
        $hydrator = new \Doctrine\ORM\Internal\Hydration\ScalarHydrator($this->_em);

        $this->setMaxRunningTime(1);
        $s = microtime(true);
        $result = $hydrator->hydrateAll($stmt, $rsm);
        $e = microtime(true);
        echo __FUNCTION__ . " - " . ($e - $s) . " seconds" . PHP_EOL;
    }

    /**
     * Times for comparison:
     *
     * [romanb: 10000 rows => 1 second]
     *
     * MAXIMUM TIME: 2 seconds
     */
    public function testSimpleQueryArrayHydrationPerformance10000Rows()
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

        $this->setMaxRunningTime(2);
        $s = microtime(true);
        $result = $hydrator->hydrateAll($stmt, $rsm);
        $e = microtime(true);
        echo __FUNCTION__ . " - " . ($e - $s) . " seconds" . PHP_EOL;
    }

    /**
     * Times for comparison:
     *
     * [romanb: 10000 rows => 1.4 seconds]
     *
     * MAXIMUM TIME: 3 seconds
     */
    public function testMixedQueryFetchJoinArrayHydrationPerformance10000Rows()
    {
        $rsm = new ResultSetMapping;
        $rsm->addEntityResult('Doctrine\Tests\Models\CMS\CmsUser', 'u');
        $rsm->addJoinedEntityResult(
                'Doctrine\Tests\Models\CMS\CmsPhonenumber',
                'p',
                'u',
                'phonenumbers'
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

        $this->setMaxRunningTime(3);
        $s = microtime(true);
        $result = $hydrator->hydrateAll($stmt, $rsm);
        $e = microtime(true);
        echo __FUNCTION__ . " - " . ($e - $s) . " seconds" . PHP_EOL;
    }

    /**
     * [romanb: 10000 rows => 1.5 seconds]
     *
     * MAXIMUM TIME: 3 seconds
     */
    public function testSimpleQueryPartialObjectHydrationPerformance10000Rows()
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

        $this->setMaxRunningTime(3);
        $s = microtime(true);
        $result = $hydrator->hydrateAll($stmt, $rsm, array(Query::HINT_FORCE_PARTIAL_LOAD => true));
        $e = microtime(true);
        echo __FUNCTION__ . " - " . ($e - $s) . " seconds" . PHP_EOL;
    }

    /**
     * [romanb: 10000 rows => 3 seconds]
     *
     * MAXIMUM TIME: 4.5 seconds
     */
    public function testSimpleQueryFullObjectHydrationPerformance10000Rows()
    {
        $rsm = new ResultSetMapping;
        $rsm->addEntityResult('Doctrine\Tests\Models\CMS\CmsUser', 'u');
        $rsm->addFieldResult('u', 'u__id', 'id');
        $rsm->addFieldResult('u', 'u__status', 'status');
        $rsm->addFieldResult('u', 'u__username', 'username');
        $rsm->addFieldResult('u', 'u__name', 'name');
        $rsm->addJoinedEntityResult(
                'Doctrine\Tests\Models\CMS\CmsAddress',
                'a',
                'u',
                'address'
        );
        $rsm->addFieldResult('a', 'a__id', 'id');
        //$rsm->addFieldResult('a', 'a__country', 'country');
        //$rsm->addFieldResult('a', 'a__zip', 'zip');
        //$rsm->addFieldResult('a', 'a__city', 'city');

        // Faked result set
        $resultSet = array(
            //row1
            array(
                'u__id' => '1',
                'u__status' => 'developer',
                'u__username' => 'romanb',
                'u__name' => 'Roman',
                'a__id' => '1'
            )
        );

        for ($i = 2; $i < 10000; ++$i) {
            $resultSet[] = array(
                'u__id' => $i,
                'u__status' => 'developer',
                'u__username' => 'jwage',
                'u__name' => 'Jonathan',
                'a__id' => $i
            );
        }

        $stmt = new HydratorMockStatement($resultSet);
        $hydrator = new \Doctrine\ORM\Internal\Hydration\ObjectHydrator($this->_em);

        $this->setMaxRunningTime(5);
        $s = microtime(true);
        $result = $hydrator->hydrateAll($stmt, $rsm);
        $e = microtime(true);
        echo __FUNCTION__ . " - " . ($e - $s) . " seconds" . PHP_EOL;
    }

    /**
     * [romanb: 2000 rows => 0.4 seconds]
     *
     * MAXIMUM TIME: 1 second
     */
    public function testMixedQueryFetchJoinPartialObjectHydrationPerformance2000Rows()
    {
        $rsm = new ResultSetMapping;
        $rsm->addEntityResult('Doctrine\Tests\Models\CMS\CmsUser', 'u');
        $rsm->addJoinedEntityResult(
                'Doctrine\Tests\Models\CMS\CmsPhonenumber',
                'p',
                'u',
                'phonenumbers'
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

        $this->setMaxRunningTime(1);
        $s = microtime(true);
        $result = $hydrator->hydrateAll($stmt, $rsm, array(Query::HINT_FORCE_PARTIAL_LOAD => true));
        $e = microtime(true);
        echo __FUNCTION__ . " - " . ($e - $s) . " seconds" . PHP_EOL;
    }

    /**
     * [romanb: 2000 rows => 0.6 seconds]
     *
     * MAXIMUM TIME: 1 second
     */
    public function testMixedQueryFetchJoinFullObjectHydrationPerformance2000Rows()
    {
        $rsm = new ResultSetMapping;
        $rsm->addEntityResult('Doctrine\Tests\Models\CMS\CmsUser', 'u');
        $rsm->addJoinedEntityResult(
                'Doctrine\Tests\Models\CMS\CmsPhonenumber',
                'p',
                'u',
                'phonenumbers'
        );
        $rsm->addFieldResult('u', 'u__id', 'id');
        $rsm->addFieldResult('u', 'u__status', 'status');
        $rsm->addFieldResult('u', 'u__username', 'username');
        $rsm->addFieldResult('u', 'u__name', 'name');
        $rsm->addScalarResult('sclr0', 'nameUpper');
        $rsm->addFieldResult('p', 'p__phonenumber', 'phonenumber');
        $rsm->addJoinedEntityResult(
                'Doctrine\Tests\Models\CMS\CmsAddress',
                'a',
                'u',
                'address'
        );
        $rsm->addFieldResult('a', 'a__id', 'id');

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
                'a__id' => '1'
            )
        );

        for ($i = 2; $i < 2000; ++$i) {
            $resultSet[] = array(
                'u__id' => $i,
                'u__status' => 'developer',
                'u__username' => 'jwage',
                'u__name' => 'Jonathan',
                'sclr0' => 'JWAGE' . $i,
                'p__phonenumber' => '91',
                'a__id' => $i
            );
        }

        $stmt = new HydratorMockStatement($resultSet);
        $hydrator = new \Doctrine\ORM\Internal\Hydration\ObjectHydrator($this->_em);

        $this->setMaxRunningTime(1);
        $s = microtime(true);
        $result = $hydrator->hydrateAll($stmt, $rsm);
        $e = microtime(true);
        echo __FUNCTION__ . " - " . ($e - $s) . " seconds" . PHP_EOL;
    }
}

