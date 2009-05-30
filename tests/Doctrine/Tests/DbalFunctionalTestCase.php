<?php

namespace Doctrine\Tests;

class DbalFunctionalTestCase extends DbalTestCase
{
    protected $_conn;

    protected function setUp()
    {
        if ( ! isset($this->_conn)) {
            $this->_conn = TestUtil::getConnection();
        }
    }
}