<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

require_once __DIR__ . '/../../../TestInit.php';

class DDC144Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    protected function setUp() {
        parent::setUp();
        //$this->_em->getConnection()->getConfiguration()->setSQLLogger(new \Doctrine\DBAL\Logging\EchoSQLLogger);

        $this->_schemaTool->createSchema(array(
            $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC144FlowElement'),
        //    $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC144Expression'),
            $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC144Operand'),
        ));

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

    }
}

/**
 * @Entity
 * @Table(name="ddc144_flowelements")
 * @InheritanceType("JOINED")
 * @DiscriminatorColumn(type="string", name="discr")
 * @DiscriminatorMap({"flowelement" = "DDC144FlowElement", "operand" = "DDC144Operand"})
 */
class DDC144FlowElement {
    /**
     * @Id @Column(type="integer") @GeneratedValue
     * @var integer
     */
    public $id;
    /** @Column */
    public $property;
}

abstract class DDC144Expression extends DDC144FlowElement {
    abstract function method();
}

/** @Entity @Table(name="ddc144_operands") */
class DDC144Operand extends DDC144Expression {
    /** @Column */
    public $operandProperty;
    function method() {}
}


