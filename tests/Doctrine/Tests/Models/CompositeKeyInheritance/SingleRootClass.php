<?php

namespace Doctrine\Tests\Models\CompositeKeyInheritance;

use Doctrine\ORM\Annotation as ORM;

/**
 * @ORM\Entity
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="discr", type="string")
 * @ORM\DiscriminatorMap({"child" = "SingleChildClass", "root" = "SingleRootClass"})
 */
class SingleRootClass
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
