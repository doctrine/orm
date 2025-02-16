<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\DiscriminatorColumn;
use Doctrine\ORM\Mapping\DiscriminatorMap;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\InheritanceType;
use Doctrine\ORM\Mapping\Table;
use Doctrine\Tests\OrmFunctionalTestCase;
use PHPUnit\Framework\Attributes\Group;

class DDC144Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchemaForModels(
            DDC144FlowElement::class,
            DDC144Operand::class,
        );
    }

    #[Group('DDC-144')]
    public function testIssue(): void
    {
        $operand                  = new DDC144Operand();
        $operand->property        = 'flowValue';
        $operand->operandProperty = 'operandValue';

        $this->_em->persist($operand);
        $this->_em->flush();

        self::assertSame($operand, $this->_em->find(DDC144Operand::class, $operand->id));
    }
}

#[Table(name: 'ddc144_flowelements')]
#[Entity]
#[InheritanceType('JOINED')]
#[DiscriminatorColumn(type: 'string', name: 'discr')]
#[DiscriminatorMap(['flowelement' => 'DDC144FlowElement', 'operand' => 'DDC144Operand'])]
class DDC144FlowElement
{
    /** @var int */
    #[Id]
    #[Column(type: 'integer')]
    #[GeneratedValue]
    public $id;

    /** @var string */
    #[Column]
    public $property;
}

abstract class DDC144Expression extends DDC144FlowElement
{
    abstract public function method(): void;
}

#[Table(name: 'ddc144_operands')]
#[Entity]
class DDC144Operand extends DDC144Expression
{
    /** @var string */
    #[Column]
    public $operandProperty;

    public function method(): void
    {
    }
}
