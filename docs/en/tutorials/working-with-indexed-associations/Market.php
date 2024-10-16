<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\StockExchange;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\Table;
use InvalidArgumentException;

#[Entity]
#[Table(name: 'exchange_markets')]
class Market
{
    #[Id]
    #[Column(type: 'integer')]
    #[GeneratedValue]
    private int|null $id = null;

    #[Column(type: 'string')]
    private string $name;

    /** @var Collection<string, Stock> */
    #[OneToMany(targetEntity: Stock::class, mappedBy: 'market', indexBy: 'symbol')]
    private Collection $stocks;

    public function __construct(string $name)
    {
        $this->name   = $name;
        $this->stocks = new ArrayCollection();
    }

    public function getId(): int|null
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
        if (! isset($this->stocks[$symbol])) {
            throw new InvalidArgumentException('Symbol is not traded on this market.');
        }

        return $this->stocks[$symbol];
    }

    /** @return array<string, Stock> */
    public function getStocks(): array
    {
        return $this->stocks->toArray();
    }
}
