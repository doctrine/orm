<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

class DDC3582Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    function testNestedEmbeddablesAreHydratedWithProperClass()
    {
        $this->_schemaTool->createSchema([
            $this->_em->getClassMetadata(DDC3582Entity::CLASSNAME),
            $this->_em->getClassMetadata(DDC3582Embeddable1::CLASSNAME),
            $this->_em->getClassMetadata(DDC3582Embeddable2::CLASSNAME),
        ]);

        $this->_em->persist(new DDC3582Entity('foo'));
        $this->_em->flush();
        $this->_em->clear();

        /** @var DDC3582Entity $entity */
        $entity = $this->_em->find(DDC3582Entity::CLASSNAME, 'foo');

        $this->assertInstanceOf(DDC3582Embeddable1::CLASSNAME, $entity->embeddable1);
        $this->assertInstanceOf(DDC3582Embeddable2::CLASSNAME, $entity->embeddable1->embeddable2);
    }
}

/** @Entity */
class DDC3582Entity
{
    const CLASSNAME = __CLASS__;

    /** @Id @Column */
    private $id;

    /** @Embedded(class="DDC3582Embeddable1") @var DDC3582Embeddable1 */
    public $embeddable1;

    function __construct($id)
    {
        $this->id = $id;
        $this->embeddable1 = new DDC3582Embeddable1();
    }
}

/** @Embeddable */
class DDC3582Embeddable1
{
    const CLASSNAME = __CLASS__;

    /** @Column */
    public $embeddedValue1 = 'foo';

    /** @Embedded(class="DDC3582Embeddable2") @var DDC3582Embeddable2 */
    public $embeddable2;
    public function __construct() { $this->embeddable2 = new DDC3582Embeddable2(); }
}

/** @Embeddable */
class DDC3582Embeddable2
{
    const CLASSNAME = __CLASS__;

    /** @Column */
    public $embeddedValue2 = 'foo';
}
