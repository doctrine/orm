<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\DiscriminatorMap;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\InheritanceType;
use Doctrine\ORM\Mapping\Version;
use Doctrine\Tests\OrmFunctionalTestCase;
use PHPUnit\Framework\Attributes\Group;

#[Group('DDC-2175')]
class DDC2175Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchemaForModels(DDC2175Entity::class);
    }

    public function testIssue(): void
    {
        $entity        = new DDC2175Entity();
        $entity->field = 'foo';

        $this->_em->persist($entity);
        $this->_em->flush();

        self::assertEquals(1, $entity->version);

        $entity->field = 'bar';
        $this->_em->flush();

        self::assertEquals(2, $entity->version);

        $entity->field = 'baz';
        $this->_em->flush();

        self::assertEquals(3, $entity->version);
    }
}

#[Entity]
#[InheritanceType('JOINED')]
#[DiscriminatorMap(['entity' => 'DDC2175Entity'])]
class DDC2175Entity
{
    /** @var int */
    #[Id]
    #[GeneratedValue]
    #[Column(type: 'integer')]
    public $id;

    /** @var string */
    #[Column(type: 'string', length: 255)]
    public $field;

    /** @var int */
    #[Version]
    #[Column(type: 'integer')]
    public $version;
}
