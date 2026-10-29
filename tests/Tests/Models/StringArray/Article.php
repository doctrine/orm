<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\StringArray;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Table;
use Doctrine\Tests\DbalTypes\StringArrayType;

#[Entity]
#[Table(name: 'string_array_articles')]
class Article
{
    /** @param list<string>|null $tags */
    public function __construct(
        #[Id]
        #[Column]
        public int $id,
        #[Column]
        public string $title,
        #[Column(type: StringArrayType::NAME, nullable: true)]
        public array|null $tags,
    ) {
    }
}
