<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\GeoNames;

use Doctrine\ORM\Mapping\Cache;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\Table;

#[Table(name: 'geonames_admin1')]
#[Entity]
#[Cache]
class Admin1
{
    /** @phpstan-var Collection<int, Admin1AlternateName> */
    #[OneToMany(targetEntity: 'Admin1AlternateName', mappedBy: 'admin1')]
    #[Cache]
    public $names = [];

    public function __construct(
        #[Id]
        #[Column(type: 'integer', length: 25)]
        #[GeneratedValue(strategy: 'NONE')]
        public int $id,
        #[Column(type: 'string', length: 255)]
        public string $name,
        #[Id]
        #[ManyToOne(targetEntity: 'Country')]
        #[JoinColumn(name: 'country', referencedColumnName: 'id')]
        #[Cache]
        public Country $country,
    ) {
    }
}
