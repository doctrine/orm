<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\GeoNames;

use Doctrine\ORM\Mapping\Cache;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Table;

#[Table(name: 'geonames_country')]
#[Entity]
#[Cache]
class Country
{
    public function __construct(
        #[Id]
        #[Column(type: 'string', length: 2)]
        #[GeneratedValue(strategy: 'NONE')]
        public string $id,
        #[Column(type: 'string', length: 255)]
        public string $name,
    ) {
    }
}
