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
use Doctrine\ORM\Mapping\Table;

#[Table(name: 'geonames_admin1_alternate_name')]
#[Entity]
#[Cache]
class Admin1AlternateName
{
    #[Id]
    #[Column(type: 'string', length: 25)]
    #[GeneratedValue(strategy: 'NONE')]
    public string $id;

    public function __construct(
        int $id,
        #[Column(type: 'string', length: 255)]
        public string $name,
        #[JoinColumn(name: 'admin1', referencedColumnName: 'id')]
        #[JoinColumn(name: 'country', referencedColumnName: 'country')]
        #[ManyToOne(targetEntity: 'Admin1', inversedBy: 'names')]
        #[Cache]
        public Admin1 $admin1,
    ) {
        $this->id = (string) $id;
    }
}
