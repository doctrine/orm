<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

class DDC3414Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    protected function setUp()
    {
        parent::setUp();
        $this->_schemaTool->createSchema(array(
            $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC3414TestSuper'),
            $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC3414TestChild'),
            $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC3414TestClass')
        ));
    }

    /**
     * @group DDC-3414
     */
    public function testIssue()
    {
        $query = $this->_em->createQuery('SELECT child, class FROM '. __NAMESPACE__ . '\DDC3414TestChild child INDEX BY child.id LEFT JOIN '. __NAMESPACE__ . '\DDC3414TestChild class WITH child.class = class');
        $query->getResult();
    }
}

/**
 * @Entity
 * @InheritanceType("JOINED")
 * @DiscriminatorColumn(name="type", type="string")
 * @DiscriminatorMap({"child" = "DDC3414TestChild"})
 */
abstract class DDC3414TestSuper
{
    /**
     * @Id @Column(name="id", type="integer")
     * @GeneratedValue(strategy="AUTO")
    */
    public $id;
}

/**
 * @Entity
 */
class DDC3414TestChild extends DDC3414TestSuper
{
    /** @OneToOne(targetEntity="DDC3414TestClass") */
    public $class;
}

/**
 * @Entity
 */
class DDC3414TestClass
{
    /**
     * @Id @Column(name="id", type="integer")
     * @GeneratedValue
     */
    public $id;
}
