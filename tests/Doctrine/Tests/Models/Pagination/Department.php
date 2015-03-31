<?php
namespace Doctrine\Tests\Models\Pagination;

/**
 * Department
 *
 * @package Doctrine\Tests\Models\Pagination
 *
 * @author Bill Schaller
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
     * @Column(type="string")
     */
    public $name;

    /**
     * @ManyToOne(targetEntity="Company", inversedBy="departments", cascade={"persist"})
     */
    public $company;
}
