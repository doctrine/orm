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
    private static $sharedConn;

    /** @var Connection */
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
