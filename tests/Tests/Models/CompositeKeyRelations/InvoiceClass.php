<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\CompositeKeyRelations;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\JoinColumns;
use Doctrine\ORM\Mapping\ManyToOne;

/** @Entity */
class InvoiceClass
{
    /**
     * @var string
     * @Id
     * @Column(type="string")
     */
    public $companyCode;

    /**
     * @var string
     * @Id
     * @Column(type="string")
     */
    public $invoiceNumber;

    /**
     * @var CustomerClass|null
     * @ManyToOne(targetEntity="CustomerClass")
     * @JoinColumns({
     *     @JoinColumn(name="companyCode", referencedColumnName="companyCode"),
     *     @JoinColumn(name="customerCode", referencedColumnName="code")
     * })
     */
    public $customer;

    /**
     * @var string|null
     * @Column(type="string", nullable=true)
     */
    public $customerCode;
}
