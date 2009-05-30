<?php

namespace Doctrine\Tests;

class DbalFunctionalTestSuite extends DbalTestSuite
{
    protected $_conn;

    protected function setUp()
    {
        if ( ! isset($this->_conn)) {
            $this->_conn = TestUtil::getConnection();
        }
    }
    
    protected function tearDown()
    {
        $this->_conn = null;
    }
}