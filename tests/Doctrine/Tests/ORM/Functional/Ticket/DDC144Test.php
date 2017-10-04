<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Tests\OrmFunctionalTestCase;

class DDC144Test extends OrmFunctionalTestCase
{
    protected function setUp() {
        parent::setUp();

        $this->_schemaTool->createSchema(
            [
                $this->_em->getClassMetadata(DDC144FlowElement::class),
                $this->_em->getClassMetadata(DDC144Operand::class),
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

        $this->_em->persist($operand);
        $this->_em->flush();

        self::assertSame($operand, $this->_em->find(DDC144Operand::class, $operand->id));
    }
}

/**
 * @Entity
 * @Table(name="ddc144_flowelements")
 * @InheritanceType("JOINED")
 * @DiscriminatorColumn(type="string", name="discr")
 * @DiscriminatorMap({"flowelement" = "DDC144FlowElement", "operand" = "DDC144Operand"})
 */
class DDC144FlowElement
{
    /**
     * @Id @Column(type="integer") @GeneratedValue
     * @var int
     */
    public $id;

    /** @Column */
    public $property;
}

abstract class DDC144Expression extends DDC144FlowElement
{
    abstract public function method();
}

/** @Entity @Table(name="ddc144_operands") */
class DDC144Operand extends DDC144Expression
{
    /** @Column */
    public $operandProperty;

    public function method()
    {
    }
}
