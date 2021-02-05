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
     * @Column(type="integer") @Id
     * @GeneratedValue
     */
    public $id;

    /** @Column(type="string") */
    public $image;

    /** @Column(type="integer") */
    public $image_height;

    /** @Column(type="integer") */
    public $image_width;

    /**
     * @OneToOne(targetEntity="Company", inversedBy="logo", cascade={"persist"})
     * @JoinColumn(name="company_id")
     */
    public $company;
}
