<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\VersionedManyToOne;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\Version;

/**
 * @Entity
 * @Table(name="ver_m2o_category")
 */
class Category
{
    /**
     * @var int
     * @Id
     * @Column(name="id", type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    public $id;

    /**
     * Version column
     *
     * @var int
     * @Column(type="integer", name="version")
     * @Version
     */
    public $version;
}
