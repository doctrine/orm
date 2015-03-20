<?php
/**
 * Company.php
 * Created by William Schaller
 * Date: 3/19/2015
 * Time: 9:39 PM
 */
namespace Doctrine\Tests\Models\Pagination;

/**
 * Company
 *
 * @package Doctrine\Tests\Models\Pagination
 *
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
