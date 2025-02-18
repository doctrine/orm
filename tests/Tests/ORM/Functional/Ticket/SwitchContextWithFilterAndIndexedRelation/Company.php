<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket\SwitchContextWithFilterAndIndexedRelation;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'Company_Master')]
class Company
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    public int $id;

    #[ORM\Column(type: 'string')]
    public string $name;

    /** @var Collection<int, Category> */
    #[ORM\ManyToMany(targetEntity: Category::class, fetch: 'EAGER', indexBy: 'type')]
    public Collection $categories;

    /** @param Category[] $categories */
    public function __construct(string $name, array $categories)
    {
        $this->name       = $name;
        $this->categories = new ArrayCollection($categories);
    }
}
