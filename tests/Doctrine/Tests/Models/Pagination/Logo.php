<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\Pagination;

/**
 * Logo
 *
 * @Author Bill Schaller
 * @Entity
 * @Table(name="pagination_logo")
 */
class Logo
{
    /**
     * @var int
     * @Column(type="integer") @Id
     * @GeneratedValue
     */
    public $id;

    /**
     * @var string
     * @Column(type="string")
     */
    public $image;

    /**
     * @var int
     * @Column(type="integer")
     */
    public $imageHeight;

    /**
     * @var int
     * @Column(type="integer")
     */
    public $imageWidth;

    /**
     * @var Company
     * @OneToOne(targetEntity="Company", inversedBy="logo", cascade={"persist"})
     * @JoinColumn(name="company_id")
     */
    public $company;
}
