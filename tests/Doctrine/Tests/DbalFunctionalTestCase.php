<?php

namespace Doctrine\Tests;

class DbalFunctionalTestCase extends DbalTestCase
{
    protected $_conn;

    protected function setUp()
    {
        if ( ! isset($this->_conn)) {
            if ( ! isset($this->sharedFixture['conn'])) {
                $this->sharedFixture['conn'] = TestUtil::getConnection();
            }
            $this->_conn = $this->sharedFixture['conn'];
        }
    }
}