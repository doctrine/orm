<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\CompositeKeyInheritance;

use Doctrine\ORM\Annotation as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name = "joined_derived_identity")
 */
class JoinedDerivedIdentityClass
{
    /**
     * @var string
     * @ORM\Column(type="string")
     * @ORM\Id
     */
    protected $id = 'part-0';

    /**
     * @var JoinedDerivedRootClass[]
     * @ORM\OneToMany(
     *     targetEntity=JoinedDerivedRootClass::class,
     *     mappedBy="keyPart1"
     * )
     */
    protected $children;
}
