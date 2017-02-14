<?php

namespace Doctrine\Tests\Models\Pagination;

use Doctrine\ORM\Annotation as ORM;

/**
 * Logo
 *
 * @package Doctrine\Tests\Models\Pagination
 * @author Bill Schaller
 *
 * @ORM\Entity
 * @ORM\Table(name="pagination_logo")
 */
class Logo
{
    /**
     * @ORM\Column(type="integer") @ORM\Id
     * @ORM\GeneratedValue
     */
    public $id;

    /**
     * @ORM\Column(type="string")
     */
    public $image;

    /**
     * @ORM\Column(type="integer")
     */
    public $image_height;

    /**
     * @ORM\Column(type="integer")
     */
    public $image_width;

    /**
     * @ORM\OneToOne(targetEntity="Company", inversedBy="logo", cascade={"persist"})
     * @ORM\JoinColumn(name="company_id")
     */
    public $company;
}
