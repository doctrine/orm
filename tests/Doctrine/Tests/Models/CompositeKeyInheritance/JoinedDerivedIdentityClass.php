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
     * @ORM\Column(type="string")
     * @ORM\Id
     *
     * @var string
     */
    protected $id = 'part-0';

    /**
     * @ORM\OneToMany(
     *     targetEntity=JoinedDerivedRootClass::class,
     *     mappedBy="keyPart1"
     * )
     *
     * @var JoinedDerivedRootClass[]
     */
    protected $children;
}
