<?php

namespace Doctrine\Tests\Models\StockExchange;

use Doctrine\ORM\Annotation as ORM;

/**
 * Bonds have many stocks. This uses a many to many association and fails to model how many of a
 * particular stock a bond has. But i Need a many-to-many association, so please bear with my modelling skills ;)
 *
 * @ORM\Entity
 * @ORM\Table(name="exchange_bonds")
 */
class Bond
{
    /**
     * @ORM\Id @ORM\GeneratedValue @ORM\Column(type="integer")
     * @var int
     */
    private $id;

    /**
     * @ORM\Column(type="string")
     * @var string
     */
    private $name;

    /**
     * @ORM\ManyToMany(targetEntity="Stock", indexBy="symbol")
     * @ORM\JoinTable(name="exchange_bonds_stocks")
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