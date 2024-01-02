<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket\GH11017;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class GH11017Entity
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     *
     * @var ?int
     */
    public $id;

    /**
     * @ORM\Column(type="string", enumType=GH11017Enum::class)
     *
     * @var GH11017Enum
     */
    public $field;
}
