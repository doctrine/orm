<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\Forum;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\Table;

#[Table(name: 'forum_categories')]
#[Entity]
class ForumCategory
{
    #[Column(type: 'integer')]
    #[Id]
    private int $id;

    /** @var int */
    #[Column(type: 'integer')]
    public $position;

    /** @var string */
    #[Column(type: 'string', length: 255)]
    public $name;

    /** @phpstan-var Collection<int, ForumBoard> */
    #[OneToMany(targetEntity: 'ForumBoard', mappedBy: 'category')]
    public $boards;

    public function getId(): int
    {
        return $this->id;
    }
}
