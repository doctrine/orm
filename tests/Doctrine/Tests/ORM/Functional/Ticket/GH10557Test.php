<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Tests\OrmTestCase;

final class GH10557Test extends OrmTestCase
{
    public function testGH10557(): void
    {
        $this->expectNotToPerformAssertions();

        $entityManager = $this->getTestEntityManager();
        $entityManager->getClassMetadata(GH10557Base::class);
    }
}

/**
 * @ORM\Entity
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="discr", type="string")
 * @ORM\DiscriminatorMap({"base" = "GH10557Base", "leaf" = "GH10557Leaf"})
 */
class GH10557Base
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     *
     * @var int
     */
    public $id;
}

/**
 * @ORM\MappedSuperclass
 */
abstract class GH10557MappedSuperclass extends GH10557Base
{
    /**
     * @ORM\Column(type="string")
     *
     * @var string
     */
    private $test;
}

/**
 * @ORM\Entity
 * @ORM\AttributeOverrides(
 *     @ORM\AttributeOverride(name="test", column=@ORM\Column(name="test_override"))
 * )
 */
abstract class GH10557OverrideEntity extends GH10557MappedSuperclass
{
}

/**
 * @ORM\Entity
 */
class GH10557Leaf extends GH10557OverrideEntity
{
}
