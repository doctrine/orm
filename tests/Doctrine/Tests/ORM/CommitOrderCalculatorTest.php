<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM;

use Doctrine\ORM\Internal\CommitOrderCalculator;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataBuildingContext;
use Doctrine\ORM\Mapping\ClassMetadataFactory;
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
    /**
     * @var CommitOrderCalculator
     */
    private $calc;

    /**
     * @var ClassMetadataBuildingContext|\PHPUnit_Framework_MockObject_MockObject
     */
    private $metadataBuildingContext;

    protected function setUp()
    {
        $this->calc                    = new CommitOrderCalculator();
        $this->metadataBuildingContext = new ClassMetadataBuildingContext(
            $this->createMock(ClassMetadataFactory::class)
        );
    }

    public function testCommitOrdering1()
    {
        $class1 = new ClassMetadata(NodeClass1::class, $this->metadataBuildingContext);
        $class2 = new ClassMetadata(NodeClass2::class, $this->metadataBuildingContext);
        $class3 = new ClassMetadata(NodeClass3::class, $this->metadataBuildingContext);
        $class4 = new ClassMetadata(NodeClass4::class, $this->metadataBuildingContext);
        $class5 = new ClassMetadata(NodeClass5::class, $this->metadataBuildingContext);

        $this->calc->addNode($class1->getClassName(), $class1);
        $this->calc->addNode($class2->getClassName(), $class2);
        $this->calc->addNode($class3->getClassName(), $class3);
        $this->calc->addNode($class4->getClassName(), $class4);
        $this->calc->addNode($class5->getClassName(), $class5);

        $this->calc->addDependency($class1->getClassName(), $class2->getClassName(), 1);
        $this->calc->addDependency($class2->getClassName(), $class3->getClassName(), 1);
        $this->calc->addDependency($class3->getClassName(), $class4->getClassName(), 1);
        $this->calc->addDependency($class5->getClassName(), $class1->getClassName(), 1);

        $sorted = $this->calc->sort();

        // There is only 1 valid ordering for this constellation
        $correctOrder = [$class5, $class1, $class2, $class3, $class4];

        self::assertSame($correctOrder, $sorted);
    }

    public function testCommitOrdering2()
    {
        $class1 = new ClassMetadata(NodeClass1::class, $this->metadataBuildingContext);
        $class2 = new ClassMetadata(NodeClass2::class, $this->metadataBuildingContext);

        $this->calc->addNode($class1->getClassName(), $class1);
        $this->calc->addNode($class2->getClassName(), $class2);

        $this->calc->addDependency($class1->getClassName(), $class2->getClassName(), 0);
        $this->calc->addDependency($class2->getClassName(), $class1->getClassName(), 1);

        $sorted = $this->calc->sort();

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
