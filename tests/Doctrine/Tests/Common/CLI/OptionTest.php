<?php

namespace Doctrine\Tests\Common\CLI;

use Doctrine\Common\CLI\Option;

require_once __DIR__ . '/../../TestInit.php';

class OptionTest extends \Doctrine\Tests\DoctrineTestCase
{
    public function testGetMethods()
    {
        $option = new Option('name', 'value', 'Description');
        
        $this->assertEquals('name', $option->getName());
        $this->assertEquals('value', $option->getDefaultValue());
        $this->assertEquals('Description', $option->getDescription());
    }
    
    public function testStringCastWithDefaultValue()
    {
        $option = new Option('name', 'value', 'Description');
        
        $this->assertEquals('--name=value', (string) $option);
    }
    
    public function testStringCastWithoutDefaultValue()
    {
        $option = new Option('name', null, 'Description');
        
        $this->assertEquals('--name', (string) $option);
    }
    
    public function testStringCastWithArrayDefaultValue()
    {
        $option = new Option('name', array('value1', 'value2'), 'Description');
        
        $this->assertEquals('--name=value1,value2', (string) $option);
    }
}