<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\Pagination;

use Doctrine\Common\Collections\Collection;

/**
 * Company
 *
 * @Entity
 * @Table(name="pagination_company")
 */
class Company
{
    /**
     * @var int
     * @Id @Column(type="integer")
     * @GeneratedValue
     */
    public $id;

    /**
     * @var string
     * @Column(type="string")
     */
    public $name;

    /**
     * @var string
     * @Column(type="string", name="jurisdiction_code", nullable=true)
     */
    public $jurisdiction;

    /**
     * @var Logo
     * @OneToOne(targetEntity="Logo", mappedBy="company", cascade={"persist"}, orphanRemoval=true)
     */
    public $logo;

    /**
     * @psalm-var Collection<int, Department>
     * @OneToMany(targetEntity="Department", mappedBy="company", cascade={"persist"}, orphanRemoval=true)
     */
    public $departments;
}
