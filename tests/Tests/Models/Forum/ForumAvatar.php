<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\Forum;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Table;

#[Table(name: 'forum_avatars')]
#[Entity]
class ForumAvatar
{
    /** @var int */
    #[Id]
    #[Column(type: 'integer')]
    #[GeneratedValue(strategy: 'AUTO')]
    public $id;
}
