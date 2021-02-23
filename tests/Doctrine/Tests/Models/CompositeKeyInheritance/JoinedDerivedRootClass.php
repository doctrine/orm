<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\CompositeKeyInheritance;

use Doctrine\ORM\Annotation as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name = "joined_derived_root")
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="discr", type="string")
 * @ORM\DiscriminatorMap({"child" = JoinedDerivedChildClass::class, "root" = JoinedDerivedRootClass::class})
 */
class JoinedDerivedRootClass
{
    /**
     * @ORM\ManyToOne(
     *     targetEntity=JoinedDerivedIdentityClass::class,
     *     inversedBy="children"
     * )
     * @ORM\Id
     *
     * @var JoinedDerivedIdentityClass
     */
    protected $keyPart1 = 'part-1';

    /**
     * @ORM\Column(type="string")
     * @ORM\Id
     *
     * @var string
     */
    protected $keyPart2 = 'part-2';
}
