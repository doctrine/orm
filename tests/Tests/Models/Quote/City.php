<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\Quote;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Table;

#[Table(name: '`quote-city`')]
#[Entity]
class City
{
    /** @var int */
    #[Id]
    #[GeneratedValue]
    #[Column(type: 'integer', name: '`city-id`')]
    public $id;

    public function __construct(
        #[Column(name: '`city-name`')]
        public string $name,
    ) {
    }
}
