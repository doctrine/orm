<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\GeoNames;

use Doctrine\ORM\Mapping\Cache;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\JoinColumns;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\Table;

/**
 * @Entity
 * @Table(name="geonames_admin1_alternate_name")
 * @Cache
 */
class Admin1AlternateName
{
    public function __construct(
        /**
         * @Id
         * @Column(type="string", length=25)
         * @GeneratedValue(strategy="NONE")
         */
        public int $id,
        /** @Column(type="string", length=255); */
        public string $name,
        /**
         * @ManyToOne(targetEntity="Admin1", inversedBy="names")
         * @JoinColumns({
         *    @JoinColumn(name="admin1", referencedColumnName="id"),
         *    @JoinColumn(name="country", referencedColumnName="country")
         * })
         * @Cache
         */
        public Admin1 $admin1,
    ) {
    }
}
