<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\Spatial;

use Doctrine\DBAL\Spatial\Geometry;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Table;

#[Entity]
#[Table(name: 'spatial_places')]
class Place
{
    public function __construct(
        #[Id]
        #[Column]
        public int $id,
        #[Column(length: 32)]
        public string $name,
        #[Column(type: 'geometry', options: ['geometryType' => 'POINT', 'srid' => 4326])]
        public Geometry $location,
        #[Column(type: 'geometry', nullable: true, options: ['srid' => 4326])]
        public Geometry|null $area = null,
    ) {
    }
}
