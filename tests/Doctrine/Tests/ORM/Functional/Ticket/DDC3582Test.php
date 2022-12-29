<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Embeddable;
use Doctrine\ORM\Mapping\Embedded;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\Tests\OrmFunctionalTestCase;

use function assert;

class DDC3582Test extends OrmFunctionalTestCase
{
    public function testNestedEmbeddablesAreHydratedWithProperClass(): void
    {
        $this->createSchemaForModels(DDC3582Entity::class);
        $this->_em->persist(new DDC3582Entity('foo'));
        $this->_em->flush();
        $this->_em->clear();

        $entity = $this->_em->find(DDC3582Entity::class, 'foo');
        assert($entity instanceof DDC3582Entity);

        self::assertInstanceOf(DDC3582Embeddable1::class, $entity->embed1);
        self::assertInstanceOf(DDC3582Embeddable2::class, $entity->embed1->embed2);
        self::assertInstanceOf(DDC3582Embeddable3::class, $entity->embed1->embed2->embed3);
    }
}

/** @Entity */
class DDC3582Entity
{
    /**
     * @var string
     * @Column
     * @Id
     */
    private $id;

    /**
     * @var DDC3582Embeddable1
     * @Embedded(class="DDC3582Embeddable1")
     */
    public $embed1;

    public function __construct(string $id)
    {
        $this->id     = $id;
        $this->embed1 = new DDC3582Embeddable1();
    }
}

/** @Embeddable */
class DDC3582Embeddable1
{
    /**
     * @var DDC3582Embeddable2
     * @Embedded(class="DDC3582Embeddable2")
     */
    public $embed2;

    public function __construct()
    {
        $this->embed2 = new DDC3582Embeddable2();
    }
}

/** @Embeddable */
class DDC3582Embeddable2
{
    /**
     * @var DDC3582Embeddable3
     * @Embedded(class="DDC3582Embeddable3")
     */
    public $embed3;

    public function __construct()
    {
        $this->embed3 = new DDC3582Embeddable3();
    }
}

/** @Embeddable */
class DDC3582Embeddable3
{
    /**
     * @var string
     * @Column
     */
    public $embedVal = 'foo';
}
