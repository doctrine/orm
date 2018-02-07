<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\CompositeKeyInheritance;

use Doctrine\ORM\Annotation as ORM;

/**
 * @ORM\Entity
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="discr", type="string")
 * @ORM\DiscriminatorMap({
 *     "child" = JoinedChildClass::class,
 *     "root" = JoinedRootClass::class
 * })
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
