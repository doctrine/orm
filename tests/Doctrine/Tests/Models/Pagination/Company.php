<?php
namespace Doctrine\Tests\Models\Pagination;

/**
 * Company
 *
 * @package Doctrine\Tests\Models\Pagination
 *
 * @author Bill Schaller
 * @Entity
 * @Table(name="pagination_company")
 */
class Company
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
     * @OneToOne(targetEntity="Logo", mappedBy="company", cascade={"persist"}, orphanRemoval=true)
     */
    public $logo;
}
