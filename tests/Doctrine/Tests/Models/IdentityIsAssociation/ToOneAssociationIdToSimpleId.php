<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\IdentityIsAssociation;

use Doctrine\ORM\Annotation as ORM;

/** @ORM\Entity */
class ToOneAssociationIdToSimpleId
{
    /**
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity=SimpleId::class)
     * @ORM\JoinColumn(name="simple_id", referencedColumnName="id")
     */
    public $simpleId;
}
