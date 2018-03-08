<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Annotation as ORM;
use Doctrine\Tests\OrmFunctionalTestCase;

/**
 * @group DDC-2175
 */
class DDC2175Test extends OrmFunctionalTestCase
{
    protected function setUp() : void
    {
        parent::setUp();

        $this->schemaTool->createSchema(
            [$this->em->getClassMetadata(DDC2175Entity::class)]
        );
    }

    protected function tearDown() : void
    {
        parent::tearDown();

        $this->schemaTool->dropSchema(
            [$this->em->getClassMetadata(DDC2175Entity::class)]
        );
    }

    public function testIssue() : void
    {
        $entity        = new DDC2175Entity();
        $entity->field = 'foo';

        $this->em->persist($entity);
        $this->em->flush();

        self::assertEquals(1, $entity->version);

        $entity->field = 'bar';
        $this->em->flush();

        self::assertEquals(2, $entity->version);

        $entity->field = 'baz';
        $this->em->flush();

        self::assertEquals(3, $entity->version);
    }
}

/**
 * @ORM\Entity
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorMap({"entity": DDC2175Entity::class})
 */
class DDC2175Entity
{
    /** @ORM\Id @ORM\GeneratedValue @ORM\Column(type="integer") */
    public $id;

    /** @ORM\Column(type="string") */
    public $field;

    /**
     * @ORM\Version
     * @ORM\Column(type="integer")
     */
    public $version;
}
