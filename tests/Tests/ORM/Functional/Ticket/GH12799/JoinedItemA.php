<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket\GH12799;

use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\Table;

#[Entity]
#[Table(name: 'gh_12799_joined_item_a')]
class JoinedItemA extends JoinedItem
{
    #[ManyToOne(targetEntity: Owner::class)]
    #[JoinColumn(name: 'owner_id')]
    public Owner|null $owner = null;
}
