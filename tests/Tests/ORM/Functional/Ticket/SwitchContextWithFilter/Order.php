<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket\SwitchContextWithFilter;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="Order_Master")
 */
class Order
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

    /**
     * @ORM\ManyToOne(targetEntity="User", fetch="EAGER")
     *
     * @var User
     */
    public $user;

    public function __construct(User $user)
    {
        $this->user    = $user;
        $this->company = $user->company;
    }
}
