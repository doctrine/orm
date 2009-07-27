<?php

namespace Doctrine\Tests\ORM;

use Doctrine\ORM\Mapping\ClassMetadata;

require_once __DIR__ . '/../TestInit.php';

/**
 * Tests of the commit order calculation.
 *
 * IMPORTANT: When writing tests here consider that a lot of graph constellations
 * can have many valid orderings, so you may want to build a graph that has only
 * 1 valid order to simplify your tests.
 */
class CommitOrderCalculatorTest extends \Doctrine\Tests\OrmTestCase
{
    private $_calc;
    
    protected function setUp()
    {
        $this->_calc = new \Doctrine\ORM\Internal\CommitOrderCalculator();
    }
    
    public function testCommitOrdering1()
    {
        $class1 = new ClassMetadata(__NAMESPACE__ . '\NodeClass1');
        $class2 = new ClassMetadata(__NAMESPACE__ . '\NodeClass2');
        $class3 = new ClassMetadata(__NAMESPACE__ . '\NodeClass3');
        $class4 = new ClassMetadata(__NAMESPACE__ . '\NodeClass4');
        $class5 = new ClassMetadata(__NAMESPACE__ . '\NodeClass5');
        
        $this->_calc->addClass($class1);
        $this->_calc->addClass($class2);
        $this->_calc->addClass($class3);
        $this->_calc->addClass($class4);
        $this->_calc->addClass($class5);
        
        $this->_calc->addDependency($class1, $class2);
        $this->_calc->addDependency($class2, $class3);
        $this->_calc->addDependency($class3, $class4);
        $this->_calc->addDependency($class5, $class1);

        $sorted = $this->_calc->getCommitOrder();
        
        // There is only 1 valid ordering for this constellation
        $correctOrder = array($class5, $class1, $class2, $class3, $class4);
        $this->assertSame($correctOrder, $sorted);
    }
}

class NodeClass1 {}
class NodeClass2 {}
class NodeClass3 {}
class NodeClass4 {}
class NodeClass5 {}