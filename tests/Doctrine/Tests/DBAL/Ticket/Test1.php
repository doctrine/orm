<?php

namespace Doctrine\Tests\DBAL\Ticket;

require_once dirname(__FILE__) . '/../../TestInit.php';
 
class Test1 extends \Doctrine\Tests\DbalTestCase
{
    public function testTest()
    {
        $this->assertEquals(0, 0);
    }
}