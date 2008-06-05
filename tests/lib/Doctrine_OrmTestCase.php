<?php
/**
 * Base testcase class for all orm testcases.
 *
 */
class Doctrine_OrmTestCase extends Doctrine_TestCase
{
    protected $_em;
    
    protected function setUp() {
        $this->_em = new Doctrine_EntityManager(new Doctrine_Connection_Mock());
    }
}
