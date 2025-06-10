<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket\SwitchContextWithFilter\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Insurance
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    public int $id;

    #[ORM\Column(type: 'string')]
    public string $name;

    #[ORM\ManyToOne(targetEntity: Practice::class)]
    public Practice $practice;

    public function __construct(Practice $practice, string $name)
    {
        $this->practice = $practice;
        $this->name     = $name;
    }
}
