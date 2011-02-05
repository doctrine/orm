<?php

namespace Doctrine\Tests\Models\StockExchange;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * @Entity
 * @Table(name="exchange_stocks")
 */
class Stock
{
    /**
     * @Id @GeneratedValue @Column(type="integer")
     * @var int
     */
    private $id;

    /**
     * For real this column would have to be unique=true. But I want to test behavior of non-unique overrides.
     *
     * @Column(type="string")
     */
    private $symbol;

    /**
     * @Column(type="decimal")
     */
    private $price;

    /**
     * @ManyToOne(targetEntity="Market", inversedBy="stocks")
     * @var Market
     */
    private $market;

    public function __construct($symbol, $initialOfferingPrice, Market $market)
    {
        $this->symbol = $symbol;
        $this->price = $initialOfferingPrice;
        $this->market = $market;
        $market->addStock($this);
    }

    public function getSymbol()
    {
        return $this->symbol;
    }
}