<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\IdentityIsAssociation;

use Doctrine\ORM\Annotation as ORM;

/** @ORM\Entity */
class NestedAssociationToToOneAssociationIdToSimpleId
{
    /**
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity=ToOneAssociationIdToSimpleId::class)
     * @ORM\JoinColumn(name="nested_id", referencedColumnName="simple_id")
     */
    public $nested;
}
