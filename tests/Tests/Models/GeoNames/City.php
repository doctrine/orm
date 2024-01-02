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

#[Table(name: 'geonames_city')]
#[Entity]
#[Cache]
class City
{
    #[Id]
    #[Column(type: 'string', length: 25)]
    #[GeneratedValue(strategy: 'NONE')]
    public string $id;

    /** @var Country */
    #[ManyToOne(targetEntity: 'Country')]
    #[JoinColumn(name: 'country', referencedColumnName: 'id')]
    #[Cache]
    public $country;

    /** @var Admin1 */
    #[JoinColumn(name: 'admin1', referencedColumnName: 'id')]
    #[JoinColumn(name: 'country', referencedColumnName: 'country')]
    #[ManyToOne(targetEntity: 'Admin1')]
    #[Cache]
    public $admin1;

    public function __construct(
        int $id,
        #[Column(type: 'string', length: 255)]
        public string $name,
    ) {
        $this->id = (string) $id;
    }
}
