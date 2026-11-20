<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket\GH12799;

use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;

#[Entity]
class SingleItemB extends SingleItem
{
    #[ManyToOne(targetEntity: Owner::class)]
    #[JoinColumn(name: 'owner_id')]
    public Owner|null $owner = null;
}
