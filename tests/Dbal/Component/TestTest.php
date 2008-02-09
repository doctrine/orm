<?php
require_once 'lib/DoctrineTestInit.php';
 
class Dbal_Component_TestTest extends Doctrine_DbalTestCase
{
    public function testTest()
    {
        $this->assertEquals(0, 0);
    }
}