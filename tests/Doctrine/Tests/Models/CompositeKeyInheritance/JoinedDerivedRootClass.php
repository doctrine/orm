<?php
namespace Doctrine\Tests\Models\CompositeKeyInheritance;

use Doctrine\ORM\Annotation as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name = "joined_derived_root")
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="discr", type="string")
 * @ORM\DiscriminatorMap({"child" = "JoinedDerivedChildClass", "root" = "JoinedDerivedRootClass"})
 */
class JoinedDerivedRootClass
{
    /**
     * @var JoinedDerivedIdentityClass
     * @ORM\ManyToOne(
     *     targetEntity="JoinedDerivedIdentityClass",
     *     inversedBy="children"
     * )
     * @ORM\Id
     */
    protected $keyPart1 = 'part-1';

    /**
     * @var string
     * @ORM\Column(type="string")
     * @ORM\Id
     */
    protected $keyPart2 = 'part-2';
}
