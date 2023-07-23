<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\Diacritics;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Table;

/**
 * @Entity
 * @Table(name="numéros_de_téléphone")
 */
class NuméroDeTéléphone
{
    /**
     * @var int
     * @Id
     * @Column(type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     * @Column(type="string")
     */
    public $numéro;

    public function getId(): int
    {
        return $this->id;
    }

    public function getNuméro(): string
    {
        return $this->numéro;
    }

    public function setNuméro(string $numéro): void
    {
        $this->numéro = $numéro;
    }
}
