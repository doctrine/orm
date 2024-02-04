<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\Tests\OrmTestCase;
use Generator;

class GH10454Test extends OrmTestCase
{
    /**
     * @param class-string $className
     *
     * @dataProvider classesThatOverrideFieldNames
     */
    public function testProtectedPropertyMustNotBeInheritedAndReconfigured(string $className): void
    {
        $em = $this->getTestEntityManager();

        $this->expectException(MappingException::class);
        $this->expectExceptionMessageMatches('/Property "field" .* was already declared, but it must be declared only once/');

        $em->getClassMetadata($className);
    }

    public function classesThatOverrideFieldNames(): Generator
    {
        yield 'Entity class that redeclares a protected field inherited from a base entity' => [GH10454EntityChildProtected::class];
        yield 'Entity class that redeclares a protected field inherited from a mapped superclass' => [GH10454MappedSuperclassChildProtected::class];
    }
}

/**
 * @ORM\Entity
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorMap({ "base": "GH10454BaseEntityProtected", "child": "GH10454EntityChildProtected" })
 * @ORM\DiscriminatorColumn(name="type")
 */
class GH10454BaseEntityProtected
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
class GH10454EntityChildProtected extends GH10454BaseEntityProtected
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
class GH10454BaseMappedSuperclassProtected
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
class GH10454MappedSuperclassChildProtected extends GH10454BaseMappedSuperclassProtected
{
    /**
     * @ORM\Column(type="text", name="child")
     *
     * @var string
     */
    protected $field;
}
