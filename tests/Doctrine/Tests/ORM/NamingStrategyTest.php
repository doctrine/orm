<?php

namespace Doctrine\Tests\ORM;

use Doctrine\ORM\DefaultNamingStrategy;

require_once __DIR__ . '/../TestInit.php';

/**
 * @group DDC-559
 */
class NamingStrategyTest extends \Doctrine\Tests\OrmTestCase
{
    public function testDefaultNamingStrategy()
    {
       $strategy = new \Doctrine\ORM\DefaultNamingStrategy();

       $this->assertEquals('ShortClassName', $strategy->classToTableName('ShortClassName'));
    }
}