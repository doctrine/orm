<?php

declare(strict_types=1);

namespace Doctrine\Tests;

use Doctrine\DBAL\Connection;

class DbalFunctionalTestCase extends DbalTestCase
{
    /**
     * Shared connection when a TestCase is run alone (outside of its functional suite).
     *
     * @var Connection|null
     */
    private static $_sharedConn;

    /** @var Connection */
    protected $_conn;

    protected function resetSharedConn(): void
    {
        $this->sharedFixture['conn'] = null;
        self::$_sharedConn           = null;
    }

    protected function setUp(): void
    {
        if (isset($this->sharedFixture['conn'])) {
            $this->_conn = $this->sharedFixture['conn'];
        } else {
            if (! isset(self::$_sharedConn)) {
                self::$_sharedConn = TestUtil::getConnection();
            }

            $this->_conn = self::$_sharedConn;
        }
    }
}
