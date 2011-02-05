<?php
namespace Doctrine\Tests\Models\StockExchange;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * Bonds have many stocks. This uses a many to many assocation and fails to model how many of a
 * particular stock a bond has. But i Need a many-to-many assocation, so please bear with my modelling skills ;)
 *
 * @Entity
 * @Table(name="exchange_bonds")
 */
class Bond
{
    /**
     * @Id @GeneratedValue @column(type="integer")
     * @var int
     */
    private $id;

    /**
     * @column(type="string")
     * @var string
     */
    private $name;

    /**
     * @ManyToMany(targetEntity="Stock", indexBy="symbol")
     * @JoinTable(name="exchange_bonds_stocks")
     * @var Stock[]
     */
    public $stocks;

    public function __construct($name)
    {
        $this->name = $name;
    }

    public function getId()
    {
        return $this->id;
    }

    public function addStock(Stock $stock)
    {
        $this->stocks[$stock->getSymbol()] = $stock;
    }
}