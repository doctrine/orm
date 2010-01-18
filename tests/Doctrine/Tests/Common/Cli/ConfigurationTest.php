<?php

namespace Doctrine\Tests\Common\Cli;

use Doctrine\Common\Cli\Configuration;

require_once __DIR__ . '/../../TestInit.php';

class ConfigurationTest extends \Doctrine\Tests\DoctrineTestCase
{
    public function testConfiguration()
    {
        $config = new Configuration();
        $config->setAttribute('name', 'value');
        
        $this->assertTrue($config->hasAttribute('name'));
        $this->assertEquals('value', $config->hasAttribute('name'));
        
        $config->setAttribute('name');
        
        $this->assertFalse($config->hasAttribute('name'));
    }
}