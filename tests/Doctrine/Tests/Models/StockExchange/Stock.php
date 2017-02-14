<?php

namespace Doctrine\Tests\Models\StockExchange;

use Doctrine\ORM\Annotation as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="exchange_stocks")
 */
class Stock
{
    /**
     * @ORM\Id @ORM\GeneratedValue @ORM\Column(type="integer")
     * @var int
     */
    private $id;

    /**
     * For real this column would have to be unique=true. But I want to test behavior of non-unique overrides.
     *
     * @ORM\Column(type="string")
     */
    private $symbol;

    /**
     * @ORM\Column(type="decimal")
     */
    private $price;

    /**
     * @ORM\ManyToOne(targetEntity="Market", inversedBy="stocks")
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