<?php
require_once 'lib/DoctrineTestInit.php';
 
class Orm_Component_TestTest extends Doctrine_OrmTestCase
{
    protected function setUp()
    {
        $this->loadFixture('forum', 'someusers');
    }
    
    public function testTest()
    {
        $this->assertEquals(0, 0);
    }
}