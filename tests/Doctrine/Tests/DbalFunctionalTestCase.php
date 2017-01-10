<?php

namespace Doctrine\Tests;

class DbalFunctionalTestCase extends DbalTestCase
{
    /**
     * Shared connection when a TestCase is run alone (outside of its functional suite).
     *
     * @var \Doctrine\DBAL\Connection|null
     */
    private static $sharedConn;

    /**
     * @var \Doctrine\DBAL\Connection
     */
    protected $conn;

    /**
     * @return void
     */
    protected function resetSharedConn()
    {
        $this->sharedFixture['conn'] = null;
        
        self::$sharedConn = null;
    }

    /**
     * @return void
     */
    protected function setUp()
    {
        if (isset($this->sharedFixture['conn'])) {
            $this->conn = $this->sharedFixture['conn'];
            
            return;
        }
        
        if (! isset(self::$sharedConn)) {
            self::$sharedConn = TestUtil::getConnection();
        }

        $this->conn = self::$sharedConn;
    }
}
