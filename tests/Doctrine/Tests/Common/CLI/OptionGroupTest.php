<?php

namespace Doctrine\Tests\Common\CLI;

use Doctrine\Common\CLI\Printers\NormalPrinter,
    Doctrine\Common\CLI\OptionGroup,
    Doctrine\Common\CLI\Option;

require_once __DIR__ . '/../../TestInit.php';

class OptionGroupTest extends \Doctrine\Tests\DoctrineTestCase
{
    private $_options = array();
    
    public function setUp()
    {
        $this->_printer = new NormalPrinter();
    
        $this->_options[0] = new Option('name', null, 'First option description');
        $this->_options[1] = new Option('another-name', 'value', 'Second option description');
        $this->_options[2] = new Option('third-name', array('value1', 'value2'), 'Third option description');
    }
    
    public function testCommonFunctionality()
    {
        $optionGroup = new OptionGroup(OptionGroup::CARDINALITY_0_N, $this->_options);
        
        $this->assertEquals(3, count($optionGroup->getOptions()));
        
        $this->assertEquals(
            '--name                      First option description' . PHP_EOL . PHP_EOL .
            '--another-name=value        Second option description' . PHP_EOL . PHP_EOL .
            '--third-name=value1,value2  Third option description' . PHP_EOL . PHP_EOL, 
            $optionGroup->formatWithDescription($this->_printer)
        );
        
        $optionGroup->clear();
        
        $this->assertEquals(0, count($optionGroup->getOptions()));
        $this->assertEquals('', $optionGroup->formatPlain($this->_printer));
        $this->assertEquals(
            'No available options' . PHP_EOL . PHP_EOL, 
            $optionGroup->formatWithDescription($this->_printer)
        );
        
        $optionGroup->addOption($this->_options[0]);
        $optionGroup->addOption($this->_options[1]);
        
        $this->assertEquals(2, count($optionGroup->getOptions()));
    }
    
    public function testCardinality0toN()
    {
        $optionGroup = new OptionGroup(OptionGroup::CARDINALITY_0_N, $this->_options);
        
        $this->assertEquals(OptionGroup::CARDINALITY_0_N, $optionGroup->getCardinality());
        
        $this->assertEquals(
            '[--name] [--another-name=value] [--third-name=value1,value2]', 
            $optionGroup->formatPlain($this->_printer)
        );
    }
    
    public function testCardinality0to1()
    {
        $optionGroup = new OptionGroup(OptionGroup::CARDINALITY_0_1, $this->_options);
        
        $this->assertEquals(OptionGroup::CARDINALITY_0_1, $optionGroup->getCardinality());
        
        $this->assertEquals(
            '[--name | --another-name=value | --third-name=value1,value2]', 
            $optionGroup->formatPlain($this->_printer)
        );
    }
    
    public function testCardinality1to1()
    {
        $optionGroup = new OptionGroup(OptionGroup::CARDINALITY_1_1, $this->_options);
        
        $this->assertEquals(OptionGroup::CARDINALITY_1_1, $optionGroup->getCardinality());
        
        $this->assertEquals(
            '(--name | --another-name=value | --third-name=value1,value2)', 
            $optionGroup->formatPlain($this->_printer)
        );
    }
    
    public function testCardinality1toN()
    {
        $optionGroup = new OptionGroup(OptionGroup::CARDINALITY_1_N, $this->_options);
        
        $this->assertEquals(OptionGroup::CARDINALITY_1_N, $optionGroup->getCardinality());
        
        $this->assertEquals(
            '(--name --another-name=value --third-name=value1,value2)', 
            $optionGroup->formatPlain($this->_printer)
        );
    }
    
    public function testCardinalityNtoN()
    {
        $optionGroup = new OptionGroup(OptionGroup::CARDINALITY_N_N, $this->_options);
        
        $this->assertEquals(OptionGroup::CARDINALITY_N_N, $optionGroup->getCardinality());
        
        $this->assertEquals(
            '--name --another-name=value --third-name=value1,value2', 
            $optionGroup->formatPlain($this->_printer)
        );
    }
    
    public function testCardinalityMtoN()
    {
        $optionGroup = new OptionGroup(OptionGroup::CARDINALITY_M_N, $this->_options);
        
        $this->assertEquals(OptionGroup::CARDINALITY_M_N, $optionGroup->getCardinality());
        
        $this->assertEquals(
            '--name --another-name=value --third-name=value1,value2', 
            $optionGroup->formatPlain($this->_printer)
        );
    }
}