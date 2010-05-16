<?php

namespace Doctrine\Tests;

class DbalFunctionalTestCase extends DbalTestCase
{
    /* Shared connection when a TestCase is run alone (outside of it's functional suite) */
    private static $_sharedConn;

    /**
     * @var Doctrine\DBAL\Connection
     */
    protected $_conn;

    protected function resetSharedConn()
    {
        $this->sharedFixture['conn'] = null;
        self::$_sharedConn = null;
    }

    protected function setUp()
    {
        if (isset($this->sharedFixture['conn'])) {
            $this->_conn = $this->sharedFixture['conn'];
        } else {
            if ( ! isset(self::$_sharedConn)) {
                self::$_sharedConn = TestUtil::getConnection();
            }
            $this->_conn = self::$_sharedConn;
        }
    }
}