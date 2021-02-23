<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Annotation as ORM;
use Doctrine\Tests\OrmFunctionalTestCase;

/**
 * @group GH-7824
 */
class GH7824Test extends OrmFunctionalTestCase
{
    protected function setUp() : void
    {
        parent::setUp();
        $this->schemaTool->createSchema([
            $this->em->getClassMetadata(GH7824Main::class),
            $this->em->getClassMetadata(GH7824Child::class),
        ]);
    }

    /**
     * Verifies that joined subclasses can contain non-ORM properties.
     */
    public function testIssue()
    {
        // Test insert
        $child               = new GH7824Child();
        $child->name         = 'Sam';
        $child->someProperty = 'foo';
        $this->em->persist($child);
        $this->em->flush();
        self::assertEquals($child->someProperty, 'foo');

        // Test update
        $child->name = 'Bob';
        $this->em->flush();
        $this->em->clear();

        // Test find
        $child = $this->em->getRepository(GH7824Child::class)->find(1);
        self::assertEquals($child->name, 'Bob');
        self::assertEquals($child->someProperty, null);
    }
}

/**
 * @ORM\Entity
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorMap({"child" = "Doctrine\Tests\ORM\Functional\Ticket\GH7824Child"})
 */
abstract class GH7824Main
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     */
    public $id;
}

/**
 * @ORM\Entity
 */
class GH7824Child extends GH7824Main
{
    /** @ORM\Column(type="string") */
    public $name;
    public $someProperty; // Not a column
}
