<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\GeoNames;

/**
 * @Entity
 * @Table(name="geonames_admin1")
 * @Cache
 */
class Admin1
{
    /**
     * @var int
     * @Id
     * @Column(type="integer", length=25)
     * @GeneratedValue(strategy="NONE")
     */
    public $id;

    /**
     * @var Country
     * @Id
     * @ManyToOne(targetEntity="Country")
     * @JoinColumn(name="country", referencedColumnName="id")
     * @Cache
     */
    public $country;

    /**
     * @psalm-var Collection<int, Admin1AlternateName>
     * @OneToMany(targetEntity="Admin1AlternateName", mappedBy="admin1")
     * @Cache
     */
    public $names = [];

    /**
     * @var string
     * @Column(type="string", length=255);
     */
    public $name;

    public function __construct(int $id, string $name, Country $country)
    {
        $this->id      = $id;
        $this->name    = $name;
        $this->country = $country;
    }
}
