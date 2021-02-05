<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\StockExchange;

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
     * @var string
     * For real this column would have to be unique=true. But I want to test behavior of non-unique overrides.
     * @Column(type="string")
     */
    private $symbol;

    /**
     * @var float
     * @Column(type="decimal")
     */
    private $price;

    /**
     * @ManyToOne(targetEntity="Market", inversedBy="stocks")
     * @var Market
     */
    private $market;

    public function __construct(string $symbol, float $initialOfferingPrice, Market $market)
    {
        $this->symbol = $symbol;
        $this->price  = $initialOfferingPrice;
        $this->market = $market;
        $market->addStock($this);
    }

    public function getSymbol(): string
    {
        return $this->symbol;
    }
}
