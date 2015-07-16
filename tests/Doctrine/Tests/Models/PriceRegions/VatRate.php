<?php
namespace Doctrine\Tests\Models\PriceRegions;

use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\JoinColumns;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\Cache;

/**
 * @Entity
 * @Table(name="vat_rates")
 * @Cache
 */
class VatRate
{
    /**
     * @Id
     * @Column(type="string", length=25)
     */

    public $id;

    /**
     * @Id
     * @Column(type="string", length=25)
     */

    public $master_user_id;

    /**
     * @Column(type="string", length=255);
     */

    public $name;

    public function __construct($id, $master_user_id, $name)
    {
        $this->id             = $id;
        $this->name           = $name;
        $this->master_user_id = $master_user_id;
    }
}
