<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket\SwitchContextWithFilterAndIndexedRelation;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="Category_Master")
 */
class Category
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     *
     * @var int
     */
    public $id;

    /**
     * @ORM\Column(type="string")
     *
     * @var string
     */
    public $name;

    /**
     * @ORM\Column(type="string")
     *
     * @var string
     */
    public $type;

    public function __construct(string $name, string $type)
    {
        $this->name = $name;
        $this->type = $type;
    }
}
