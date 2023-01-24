<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\Tests\OrmTestCase;
use Generator;

class GH10450Test extends OrmTestCase
{
    /**
     * @param class-string $className
     *
     * @dataProvider classesThatOverrideFieldNames
     */
    public function testDuplicatePrivateFieldsShallBeRejected(string $className): void
    {
        $em = $this->getTestEntityManager();

        $this->expectException(MappingException::class);

        $em->getClassMetadata($className);
    }

    public function classesThatOverrideFieldNames(): Generator
    {
        yield 'Entity class that redeclares a private field inherited from a base entity' => [GH10450EntityChild::class];
        yield 'Entity class that redeclares a private field inherited from a mapped superclass' => [GH10450MappedSuperclassChild::class];
    }
}

/**
 * @ORM\Entity
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorMap({ "base": "GH10450BaseEntity", "child": "GH10450EntityChild" })
 * @ORM\DiscriminatorColumn(name="type")
 */
class GH10450BaseEntity
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     *
     * @var int
     */
    private $id;

    /**
     * @ORM\Column(type="text", name="base")
     *
     * @var string
     */
    private $field;
}

/**
 * @ORM\Entity
 */
class GH10450EntityChild extends GH10450BaseEntity
{
    /**
     * @ORM\Column(type="text", name="child")
     *
     * @var string
     */
    private $field;
}

/**
 * @ORM\MappedSuperclass
 */
class GH10450BaseMappedSuperclass
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     *
     * @var int
     */
    private $id;

    /**
     * @ORM\Column(type="text", name="base")
     *
     * @var string
     */
    private $field;
}

/**
 * @ORM\Entity
 */
class GH10450MappedSuperclassChild extends GH10450BaseMappedSuperclass
{
    /**
     * @ORM\Column(type="text", name="child")
     *
     * @var string
     */
    private $field;
}
