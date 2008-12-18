<?php
/**
 * Base testcase class for all dbal testcases.
 */
class Doctrine_DbalTestCase extends Doctrine_TestCase
{
    protected $_conn;
    
    /**
     * setUp()
     */
    protected function setUp()
    {
        // Setup a db connection if there is none, yet. This makes it possible
        // to run tests that use a connection standalone.
        if (isset($this->sharedFixture['conn'])) {
            $this->_conn = $this->sharedFixture['conn'];
        } else {
            $this->sharedFixture['conn'] = Doctrine_TestUtil::getConnection();
            $this->_conn = $this->sharedFixture['conn'];
        }
    }
}