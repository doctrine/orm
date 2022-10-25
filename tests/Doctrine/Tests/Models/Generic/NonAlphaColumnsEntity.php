<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\Generic;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Table;

#[Table(name: '`not-a-simple-entity`')]
#[Entity]
class NonAlphaColumnsEntity
{
    /** @var int */
    #[Id]
    #[Column(type: 'integer', name: '`simple-entity-id`')]
    #[GeneratedValue(strategy: 'AUTO')]
    public $id;

    public function __construct(
        #[Column(type: 'string', length: 255, name: '`simple-entity-value`')]
        public string $value,
    ) {
    }
}
