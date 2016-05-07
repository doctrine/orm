<?php

namespace Doctrine\Tests\ORM;

use Doctrine\ORM\Internal\CommitOrderCalculator;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Tests\OrmTestCase;

/**
 * Tests of the commit order calculation.
 *
 * IMPORTANT: When writing tests here consider that a lot of graph constellations
 * can have many valid orderings, so you may want to build a graph that has only
 * 1 valid order to simplify your tests.
 */
class CommitOrderCalculatorTest extends OrmTestCase
{
    private $_calc;

    protected function setUp()
    {
        $this->_calc = new CommitOrderCalculator();
    }

    public function testCommitOrdering1()
    {
        $class1 = new ClassMetadata(NodeClass1::class);
        $class2 = new ClassMetadata(NodeClass2::class);
        $class3 = new ClassMetadata(NodeClass3::class);
        $class4 = new ClassMetadata(NodeClass4::class);
        $class5 = new ClassMetadata(NodeClass5::class);

        $this->_calc->addNode($class1->name, $class1);
        $this->_calc->addNode($class2->name, $class2);
        $this->_calc->addNode($class3->name, $class3);
        $this->_calc->addNode($class4->name, $class4);
        $this->_calc->addNode($class5->name, $class5);

        $this->_calc->addDependency($class1->name, $class2->name, 1);
        $this->_calc->addDependency($class2->name, $class3->name, 1);
        $this->_calc->addDependency($class3->name, $class4->name, 1);
        $this->_calc->addDependency($class5->name, $class1->name, 1);

        $sorted = $this->_calc->sort();

        // There is only 1 valid ordering for this constellation
        $correctOrder = [$class5, $class1, $class2, $class3, $class4];

        self::assertSame($correctOrder, $sorted);
    }

    public function testCommitOrdering2()
    {
        $class1 = new ClassMetadata(NodeClass1::class);
        $class2 = new ClassMetadata(NodeClass2::class);

        $this->_calc->addNode($class1->name, $class1);
        $this->_calc->addNode($class2->name, $class2);

        $this->_calc->addDependency($class1->name, $class2->name, 0);
        $this->_calc->addDependency($class2->name, $class1->name, 1);

        $sorted = $this->_calc->sort();

        // There is only 1 valid ordering for this constellation
        $correctOrder = [$class2, $class1];

        self::assertSame($correctOrder, $sorted);
    }
}

class NodeClass1 {}
class NodeClass2 {}
class NodeClass3 {}
class NodeClass4 {}
class NodeClass5 {}
