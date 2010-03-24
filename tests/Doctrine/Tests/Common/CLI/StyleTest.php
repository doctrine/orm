<?php

namespace Doctrine\Tests\Common\CLI;

use Doctrine\Common\CLI\Style;

require_once __DIR__ . '/../../TestInit.php';

class StyleTest extends \Doctrine\Tests\DoctrineTestCase
{
    public function testGetMethods()
    {
        $style = new Style('BLACK', 'WHITE', array('BOLD' => true));
        
        $this->assertEquals('BLACK', $style->getForeground());
        $this->assertEquals('WHITE', $style->getBackground());
        $this->assertEquals(array('BOLD' => true), $style->getOptions());
    }
}