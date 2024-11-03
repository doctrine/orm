<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\StockExchange;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\Table;

#[Table(name: 'exchange_markets')]
#[Entity]
class Market
{
    #[Id]
    #[Column(type: 'integer')]
    #[GeneratedValue]
    private int $id;

    /** @phpstan-var ArrayCollection<string, Stock> */
    #[OneToMany(targetEntity: 'Stock', mappedBy: 'market', indexBy: 'symbol')]
    public $stocks;

    public function __construct(
        #[Column(type: 'string', length: 255)]
        private string $name,
    ) {
        $this->stocks = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function addStock(Stock $stock): void
    {
        $this->stocks[$stock->getSymbol()] = $stock;
    }

    public function getStock(string $symbol): Stock
    {
        return $this->stocks[$symbol];
    }
}
