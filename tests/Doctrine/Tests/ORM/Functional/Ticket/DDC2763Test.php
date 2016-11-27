<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

class DDC2763Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    protected function setUp()
    {
        parent::setUp();
        $this->_schemaTool->createSchema(array(
            $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC2763MainTable'),
            $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC2763SubTable'),
            $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC2763TableWithAssoc'),
        ));
    }

    public function testIssue()
    {
        $sub = new DDC2763SubTable('value', 'foobar');
        $assoc = new DDC2763TableWithAssoc();
        $assoc->setReference($sub);

        $this->_em->persist($sub);
        $this->_em->persist($assoc);
        $this->_em->flush();

        $this->_em->clear();

        $entity = $this->_em->find(__NAMESPACE__ . '\DDC2763TableWithAssoc', 1);
        $reference = $entity->getReference();

        $this->assertInstanceOf(__NAMESPACE__ . '\DDC2763SubTable', $reference);
        $this->assertInstanceOf('Doctrine\ORM\Proxy\Proxy', $reference);
        $this->assertEquals('foobar', $reference->getSubField());
    }
}

/**
 * @Entity
 * @Table
 * @InheritanceType("JOINED")
 * @DiscriminatorColumn(name="discr", type="smallint")
 * @DiscriminatorMap({
 *     "0" = "DDC2763MainTable",
 *     "1" = "DDC2763SubTable"
 * })
 */
class DDC2763MainTable
{
    /**
     * @Id
     * @Column(name="id", type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @Column(type="string")
     */
    private $mainField;

    public function __construct($mainField)
    {
        $this->mainField = $mainField;
    }
}

/**
 * @Entity
 * @Table
 */
class DDC2763SubTable extends DDC2763MainTable
{
    /**
     * @Column(type="string")
     */
    private $subField;

    public function __construct($mainField, $subField)
    {
        parent::__construct($mainField);
        $this->subField = $subField;
    }

    public function getSubField()
    {
        return $this->subField;
    }
}

/**
 * @Entity
 * @Table
 */
class DDC2763TableWithAssoc
{
    /**
     * @Id
     * @Column(name="id", type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ManyToOne(targetEntity="Doctrine\Tests\ORM\Functional\Ticket\DDC2763MainTable", fetch="USE_PROXY")
     * @JoinColumn(referencedColumnName="id")
     */
    private $reference;

    public function setReference($reference)
    {
        $this->reference = $reference;
    }

    public function getReference()
    {
        return $this->reference;
    }
}


