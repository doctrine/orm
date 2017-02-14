<?php

namespace Doctrine\Tests\Models\Pagination;

use Doctrine\ORM\Annotation as ORM;

/**
 * Department
 *
 * @package Doctrine\Tests\Models\Pagination
 * @author Bill Schaller
 *
 * @ORM\Entity
 * @ORM\Table(name="pagination_department")
 */
class Department
{
    /**
     * @ORM\Id @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     */
    public $id;

    /**
     * @ORM\Column(type="string")
     */
    public $name;

    /**
     * @ORM\ManyToOne(targetEntity="Company", inversedBy="departments", cascade={"persist"})
     */
    public $company;
}
