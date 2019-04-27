<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\ORM\Internal\CommitOrderCalculator;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataBuildingContext;
use Doctrine\ORM\Mapping\ClassMetadataFactory;
use Doctrine\ORM\Reflection\ReflectionService;
use Doctrine\Tests\OrmTestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Tests of the commit order calculation.
 *
 * IMPORTANT: When writing tests here consider that a lot of graph constellations
 * can have many valid orderings, so you may want to build a graph that has only
 * 1 valid order to simplify your tests.
 */
class CommitOrderCalculatorTest extends OrmTestCase
{
    /** @var CommitOrderCalculator */
    private $calc;

    /** @var ClassMetadataBuildingContext|MockObject */
    private $metadataBuildingContext;

    protected function setUp() : void
    {
        $this->calc                    = new CommitOrderCalculator();
        $this->metadataBuildingContext = new ClassMetadataBuildingContext(
            $this->createMock(ClassMetadataFactory::class),
            $this->createMock(ReflectionService::class),
            $this->createMock(AbstractPlatform::class)
        );
    }

    public function testCommitOrdering1() : void
    {
        $class1 = new ClassMetadata(NodeClass1::class, null, $this->metadataBuildingContext);
        $class2 = new ClassMetadata(NodeClass2::class, null, $this->metadataBuildingContext);
        $class3 = new ClassMetadata(NodeClass3::class, null, $this->metadataBuildingContext);
        $class4 = new ClassMetadata(NodeClass4::class, null, $this->metadataBuildingContext);
        $class5 = new ClassMetadata(NodeClass5::class, null, $this->metadataBuildingContext);

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

    public function testCommitOrdering2() : void
    {
        $class1 = new ClassMetadata(NodeClass1::class, null, $this->metadataBuildingContext);
        $class2 = new ClassMetadata(NodeClass2::class, null, $this->metadataBuildingContext);

        $this->calc->addNode($class1->getClassName(), $class1);
        $this->calc->addNode($class2->getClassName(), $class2);

        $this->calc->addDependency($class1->getClassName(), $class2->getClassName(), 0);
        $this->calc->addDependency($class2->getClassName(), $class1->getClassName(), 1);

        $sorted = $this->calc->sort();

        // There is only 1 valid ordering for this constellation
        $correctOrder = [$class2, $class1];

        self::assertSame($correctOrder, $sorted);
    }

    public function testCommitOrdering3()
    {
        // this test corresponds to the GH7259Test::testPersistFileBeforeVersion functional test
        $class1 = new ClassMetadata(NodeClass1::class, null, $this->metadataBuildingContext);
        $class2 = new ClassMetadata(NodeClass2::class, null, $this->metadataBuildingContext);
        $class3 = new ClassMetadata(NodeClass3::class, null, $this->metadataBuildingContext);
        $class4 = new ClassMetadata(NodeClass4::class, null, $this->metadataBuildingContext);

        $this->calc->addNode($class1->getClassName(), $class1);
        $this->calc->addNode($class2->getClassName(), $class2);
        $this->calc->addNode($class3->getClassName(), $class3);
        $this->calc->addNode($class4->getClassName(), $class4);

        $this->calc->addDependency($class4->getClassName(), $class1->getClassName(), 1);
        $this->calc->addDependency($class1->getClassName(), $class2->getClassName(), 1);
        $this->calc->addDependency($class4->getClassName(), $class3->getClassName(), 1);
        $this->calc->addDependency($class1->getClassName(), $class4->getClassName(), 0);

        $sorted = $this->calc->sort();

        // There is only multiple valid ordering for this constellation, but
        // the class4, class1, class2 ordering is important to break the cycle
        // on the nullable link.
        $correctOrders = [
            [$class4, $class1, $class2, $class3],
            [$class4, $class1, $class3, $class2],
            [$class4, $class3, $class1, $class2],
        ];

        // We want to perform a strict comparison of the array
        $this->assertContains($sorted, $correctOrders, '', false, true);
    }
}

class NodeClass1
{
}
class NodeClass2
{
}
class NodeClass3
{
}
class NodeClass4
{
}
class NodeClass5
{
}
