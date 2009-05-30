<?php

namespace Doctrine\Tests;

class DbalFunctionalTestSuite extends DbalTestSuite
{
    protected function setUp()
    {
        if ( ! isset($this->sharedFixture['conn'])) {
            $this->sharedFixture['conn'] = TestUtil::getConnection();
        }
    }
    
    protected function tearDown()
    {
        $this->sharedFixture['conn']->close();
        $this->sharedFixture = null;
    }
}