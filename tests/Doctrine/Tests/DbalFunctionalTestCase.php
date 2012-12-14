<?php

namespace Doctrine\Tests;

class DbalFunctionalTestCase extends DbalTestCase
{
    /**
     * Shared connection when a TestCase is run alone (outside of its functional suite).
     *
     * @var \Doctrine\DBAL\Connection|null
     */
    private static $_sharedConn;

    /**
     * @var \Doctrine\DBAL\Connection
     */
    protected $_conn;

    /**
     * @return void
     */
    protected function resetSharedConn()
    {
        $this->sharedFixture['conn'] = null;
        self::$_sharedConn = null;
    }

    /**
     * @return void
     */
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
