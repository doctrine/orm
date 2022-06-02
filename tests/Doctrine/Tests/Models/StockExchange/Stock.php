<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\StockExchange;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\Table;

/**
 * @Entity
 * @Table(name="exchange_stocks")
 */
class Stock
{
    /**
     * @Id
     * @GeneratedValue
     * @Column(type="integer")
     */
    private int $id;

    public function __construct(
        /**
         * For real this column would have to be unique=true. But I want to test behavior of non-unique overrides.
         *
         * @Column(type="string", length=255)
         */
        private string $symbol,
        /**
         * @Column(type="decimal")
         */
        private float $price,
        /**
         * @ManyToOne(targetEntity="Market", inversedBy="stocks")
         */
        private Market $market
    ) {
        $market->addStock($this);
    }

    public function getSymbol(): string
    {
        return $this->symbol;
    }
}
