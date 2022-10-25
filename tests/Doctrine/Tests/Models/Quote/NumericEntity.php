<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\Quote;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Table;

#[Table(name: 'table')]
#[Entity]
class NumericEntity
{
    /** @var int */
    #[Id]
    #[Column(type: 'integer', name: '`1:1`')]
    #[GeneratedValue(strategy: 'AUTO')]
    public $id;

    public function __construct(
        #[Column(type: 'string', length: 255, name: '`2:2`')]
        public string $value,
    ) {
    }
}
