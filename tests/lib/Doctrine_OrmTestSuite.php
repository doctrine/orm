<?php 
/**
 * The outermost test suite for all orm related testcases & suites.
 *
 * Currently the orm suite uses a normal connection object.
 * Upon separation of the DBAL and ORM package this suite should just use a orm
 * connection/session/manager instance as the shared fixture.
 */
class Doctrine_OrmTestSuite extends Doctrine_TestSuite
{
    protected function setUp()
    {
        // @todo Make DBMS choice configurable
        //$pdo = new PDO('sqlite::memory:');
        //$this->sharedFixture['connection'] = $this->loadConnection($pdo, 'sqlite_memory');
        $this->sharedFixture['connection'] = Doctrine_TestUtil::getConnection();
    }

    protected function loadConnection($conn, $name)
    {
        return Doctrine_Manager::connection($conn, $name);
    }
    
    protected function tearDown()
    {} 
}