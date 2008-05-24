<?php
/**
 * Base testcase class for all orm testcases.
 *
 */
class Doctrine_OrmTestCase extends Doctrine_TestCase
{
    protected function setUp() {
        $em = new Doctrine_EntityManager(new Doctrine_Connection_Mock());
    }
}
