<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket\SwitchContextWithFilter;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="User_Master")
 */
class User
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
    public $company;

    public function __construct(string $company)
    {
        $this->company = $company;
    }
}
