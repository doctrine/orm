<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Annotation as ORM;

/**
 * @group embedded
 */
class DDC3582Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    public function testNestedEmbeddablesAreHydratedWithProperClass()
    {
        $this->schemaTool->createSchema([$this->em->getClassMetadata(DDC3582Entity::class)]);
        $this->em->persist(new DDC3582Entity('foo'));
        $this->em->flush();
        $this->em->clear();

        /** @var DDC3582Entity $entity */
        $entity = $this->em->find(DDC3582Entity::class, 'foo');

        self::assertInstanceOf(DDC3582Embeddable1::class, $entity->embeddable1);
        self::assertInstanceOf(DDC3582Embeddable2::class, $entity->embeddable1->embeddable2);
        self::assertInstanceOf(DDC3582Embeddable3::class, $entity->embeddable1->embeddable2->embeddable3);
    }
}

/** @ORM\Entity */
class DDC3582Entity
{
    /** @ORM\Column @ORM\Id */
    private $id;

    /** @ORM\Embedded(class="DDC3582Embeddable1") @var DDC3582Embeddable1 */
    public $embeddable1;

    public function __construct($id)
    {
        $this->id = $id;
        $this->embeddable1 = new DDC3582Embeddable1();
    }
}

/** @ORM\Embeddable */
class DDC3582Embeddable1
{
    /** @ORM\Embedded(class="DDC3582Embeddable2") @var DDC3582Embeddable2 */
    public $embeddable2;

    public function __construct() { $this->embeddable2 = new DDC3582Embeddable2(); }
}

/** @ORM\Embeddable */
class DDC3582Embeddable2
{
    /** @ORM\Embedded(class="DDC3582Embeddable3") @var DDC3582Embeddable3 */
    public $embeddable3;

    public function __construct() { $this->embeddable3 = new DDC3582Embeddable3(); }
}

/** @ORM\Embeddable */
class DDC3582Embeddable3
{
    /** @ORM\Column */
    public $embeddedValue = 'foo';
}
