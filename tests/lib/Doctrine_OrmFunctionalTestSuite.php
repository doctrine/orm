<?php

#namespace Doctrine\Tests;

/**
 * The outermost test suite for all orm related testcases & suites.
 *
 * Currently the orm suite uses a normal connection object.
 * Upon separation of the DBAL and ORM package this suite should just use a orm
 * connection/session/manager instance as the shared fixture.
 */
class Doctrine_OrmFunctionalTestSuite extends Doctrine_OrmTestSuite
{
    protected function setUp()
    {
        if ( ! isset($this->sharedFixture['conn'])) {
            $this->sharedFixture['conn'] = Doctrine_TestUtil::getConnection();
        }
    }
    
    protected function tearDown()
    {
        $this->sharedFixture = null;
    }
}