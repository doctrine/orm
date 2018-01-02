<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\IdentityIsAssociation;

use Doctrine\ORM\Annotation as ORM;

/** @ORM\Entity */
class ToOneCompositeAssociationToMultipleSimpleId
{
    /**
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity=SimpleId::class)
     * @ORM\JoinColumn(name="simple_id_a", referencedColumnName="id")
     */
    public $simpleIdA;

    /**
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity=SimpleId::class)
     * @ORM\JoinColumn(name="simple_id_b", referencedColumnName="id")
     */
    public $simpleIdB;
}
