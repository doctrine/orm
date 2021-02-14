<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\Pagination;

/**
 * Department
 *
 * @Entity
 * @Table(name="pagination_department")
 */
class Department
{
    /**
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
     * @var Company
     * @ManyToOne(targetEntity="Company", inversedBy="departments", cascade={"persist"})
     */
    public $company;
}
