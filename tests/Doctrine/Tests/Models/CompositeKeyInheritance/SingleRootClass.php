<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\CompositeKeyInheritance;

use Doctrine\ORM\Annotation as ORM;

/**
 * @ORM\Entity
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="discr", type="string")
 * @ORM\DiscriminatorMap({
 *     "child" = SingleChildClass::class,
 *     "root" = SingleRootClass::class
 * })
 */
class SingleRootClass
{
    /**
     * @ORM\Column(type="string")
     * @ORM\Id
     *
     * @var string
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
