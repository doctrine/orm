<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket\SwitchContextWithFilterAndIndexedRelation;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="Company_Master")
 */
class Company
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
     * @ORM\ManyToMany(targetEntity="Category", fetch="EAGER", indexBy="type")
     *
     * @var Collection<int, Category>
     */
    public $categories;

    /** @param Category[] $categories */
    public function __construct(string $name, array $categories)
    {
        $this->name       = $name;
        $this->categories = new ArrayCollection($categories);
    }
}
