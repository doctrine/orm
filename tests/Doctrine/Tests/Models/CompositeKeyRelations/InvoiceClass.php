<?php
namespace Doctrine\Tests\Models\CompositeKeyRelations;

/**
 * @Entity
 */
class InvoiceClass
{
    /** @Id @Column(type="string") */
    public $companyCode;

    /** @Id @Column(type="string") */
    public $invoiceNumber;

    /**
     * @ManyToOne(targetEntity="CustomerClass")
     * @JoinColumns({
     *     @JoinColumn(name="companyCode", referencedColumnName="companyCode"),
     *     @JoinColumn(name="customerCode", referencedColumnName="code")
     * })
     */
    public $customer;

    /** @Column(type="string", nullable=true) */
    public $customerCode;
}
