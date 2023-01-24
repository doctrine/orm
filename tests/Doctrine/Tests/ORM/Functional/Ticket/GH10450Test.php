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
        yield 'Entity class that redeclares a private field inherited from a base entity' => [GH10450EntityChildPrivate::class];
        yield 'Entity class that redeclares a private field inherited from a mapped superclass' => [GH10450MappedSuperclassChildPrivate::class];
        yield 'Entity class that redeclares a protected field inherited from a base entity' => [GH10450EntityChildProtected::class];
        yield 'Entity class that redeclares a protected field inherited from a mapped superclass' => [GH10450MappedSuperclassChildProtected::class];
    }
}

/**
 * @ORM\Entity
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorMap({ "base": "GH10450BaseEntityPrivate", "child": "GH10450EntityChildPrivate" })
 * @ORM\DiscriminatorColumn(name="type")
 */
class GH10450BaseEntityPrivate
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
class GH10450EntityChildPrivate extends GH10450BaseEntityPrivate
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
class GH10450BaseMappedSuperclassPrivate
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
class GH10450MappedSuperclassChildPrivate extends GH10450BaseMappedSuperclassPrivate
{
    /**
     * @ORM\Column(type="text", name="child")
     *
     * @var string
     */
    private $field;
}

/**
 * @ORM\Entity
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorMap({ "base": "GH10450BaseEntityProtected", "child": "GH10450EntityChildProtected" })
 * @ORM\DiscriminatorColumn(name="type")
 */
class GH10450BaseEntityProtected
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     *
     * @var int
     */
    protected $id;

    /**
     * @ORM\Column(type="text", name="base")
     *
     * @var string
     */
    protected $field;
}

/**
 * @ORM\Entity
 */
class GH10450EntityChildProtected extends GH10450BaseEntityProtected
{
    /**
     * @ORM\Column(type="text", name="child")
     *
     * @var string
     */
    protected $field;
}

/**
 * @ORM\MappedSuperclass
 */
class GH10450BaseMappedSuperclassProtected
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     *
     * @var int
     */
    protected $id;

    /**
     * @ORM\Column(type="text", name="base")
     *
     * @var string
     */
    protected $field;
}

/**
 * @ORM\Entity
 */
class GH10450MappedSuperclassChildProtected extends GH10450BaseMappedSuperclassProtected
{
    /**
     * @ORM\Column(type="text", name="child")
     *
     * @var string
     */
    protected $field;
}
