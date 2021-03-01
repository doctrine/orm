<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\Navigation;

/**
 * @Entity
 * @Table(name="navigation_photos")
 */
class NavPhotos
{
    /**
     * @var int
     * @Id
     * @column(type="integer")
     * @generatedValue
     */
    private $id;

    /**
     * @var NavPointOfInterest
     * @ManyToOne(targetEntity="NavPointOfInterest")
     * @JoinColumns({
     *   @JoinColumn(name="poi_long", referencedColumnName="nav_long"),
     *   @JoinColumn(name="poi_lat", referencedColumnName="nav_lat")
     * })
     */
    private $poi;

    /**
     * @var string
     * @column(type="string", name="file_name")
     */
    private $file;

    public function __construct(NavPointOfInterest $poi, string $file)
    {
        $this->poi  = $poi;
        $this->file = $file;
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
