<?php
require_once 'lib/DoctrineTestInit.php';
 
class Orm_Component_TestTest extends Doctrine_OrmTestCase
{
    public function testTest()
    {
        $this->assertEquals(0, 0);
    }
}