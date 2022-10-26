<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\Navigation;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\Table;

#[Table(name: 'navigation_photos')]
#[Entity]
class NavPhotos
{
    #[Id]
    #[Column(type: 'integer')]
    #[GeneratedValue]
    private int $id;

    public function __construct(
        #[JoinColumn(name: 'poi_long', referencedColumnName: 'nav_long')]
        #[JoinColumn(name: 'poi_lat', referencedColumnName: 'nav_lat')]
        #[ManyToOne(targetEntity: 'NavPointOfInterest')]
        private NavPointOfInterest $poi,
        #[Column(type: 'string', length: 255, name: 'file_name')]
        private string $file,
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getPointOfInterest(): NavPointOfInterest
    {
        return $this->poi;
    }

    public function getFile(): string
    {
        return $this->file;
    }
}
