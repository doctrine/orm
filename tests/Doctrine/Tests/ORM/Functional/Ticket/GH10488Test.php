<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Tests\OrmFunctionalTestCase;

class GH10488Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchemaForModels(
            GH10488Root::class,
            GH10488A::class,
            GH10488B::class
        );
    }

    public function testTwoSubclassesWithCollidingColumnDefinitions(): void
    {
        $entityA        = new GH10488A();
        $entityA->value = 42;
        $this->_em->persist($entityA);

        $entityB        = new GH10488B();
        $entityB->value = 'test';
        $this->_em->persist($entityB);

        $this->_em->flush();
        $this->_em->clear();

        $loadedEntityA = $this->_em->find(GH10488A::class, $entityA->id);
        $loadedEntityB = $this->_em->find(GH10488B::class, $entityB->id);

        self::assertSame($entityA->value, $loadedEntityA->value);
        self::assertSame($entityB->value, $loadedEntityB->value);
    }
}

/**
 * @ORM\Entity
 * @ORM\Table(name="root")
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorMap({ "A": "GH10488A", "B": "GH10488B" })
 */
abstract class GH10488Root
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     *
     * @var int
     */
    public $id;
}

/**
 * @ORM\Entity
 */
class GH10488A extends GH10488Root
{
    /**
     * @ORM\Column(type="integer")
     *
     * @var int
     */
    public $value;
}

/**
 * @ORM\Entity
 */
class GH10488B extends GH10488Root
{
    /**
     * @ORM\Column(type="string")
     *
     * @var string
     */
    public $value;
}
