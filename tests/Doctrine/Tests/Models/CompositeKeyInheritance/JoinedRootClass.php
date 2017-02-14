<?php

namespace Doctrine\Tests\Models\CompositeKeyInheritance;

use Doctrine\ORM\Annotation as ORM;

/**
 * @ORM\Entity
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="discr", type="string")
 * @ORM\DiscriminatorMap({"child" = "JoinedChildClass", "root" = "JoinedRootClass"})
 */
class JoinedRootClass
{
    /**
     * @var string
     * @ORM\Column(type="string")
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
