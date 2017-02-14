<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Annotation as ORM;
use Doctrine\Tests\OrmFunctionalTestCase;

class DDC144Test extends OrmFunctionalTestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->schemaTool->createSchema(
            [
                $this->em->getClassMetadata(DDC144FlowElement::class),
                $this->em->getClassMetadata(DDC144Operand::class),
            ]
        );

    }

    /**
     * @group DDC-144
     */
    public function testIssue()
    {
        $operand = new DDC144Operand;
        $operand->property = 'flowValue';
        $operand->operandProperty = 'operandValue';

        $this->em->persist($operand);
        $this->em->flush();

        self::assertSame($operand, $this->em->find(DDC144Operand::class, $operand->id));
    }
}

/**
 * @ORM\Entity
 * @ORM\Table(name="ddc144_flowelements")
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(type="string", name="discr")
 * @ORM\DiscriminatorMap({"flowelement" = "DDC144FlowElement", "operand" = "DDC144Operand"})
 */
class DDC144FlowElement
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     *
     * @var int
     */
    public $id;

    /** @ORM\Column */
    public $property;
}

abstract class DDC144Expression extends DDC144FlowElement
{
    abstract public function method();
}

/** @ORM\Entity @ORM\Table(name="ddc144_operands") */
class DDC144Operand extends DDC144Expression {
    /** @ORM\Column */
    public $operandProperty;

    public function method()
    {
    }
}
