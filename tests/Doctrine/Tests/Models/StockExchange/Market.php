<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\StockExchange;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Annotation as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="exchange_markets")
 */
class Market
{
    /**
     * @ORM\Id @ORM\Column(type="integer") @ORM\GeneratedValue
     *
     * @var int
     */
    private $id;

    /**
     * @ORM\Column(type="string")
     *
     * @var string
     */
    private $name;

    /**
     * @ORM\OneToMany(targetEntity=Stock::class, mappedBy="market", indexBy="symbol")
     *
     * @var Stock[]
     */
    public $stocks;

    public function __construct($name)
    {
        $this->name   = $name;
        $this->stocks = new ArrayCollection();
    }

    public function getId()
    {
        return $this->id;
    }

    public function getName()
    {
        return $this->name;
    }

    public function addStock(Stock $stock)
    {
        $this->stocks[$stock->getSymbol()] = $stock;
    }

    public function getStock($symbol)
    {
        return $this->stocks[$symbol];
    }
}
