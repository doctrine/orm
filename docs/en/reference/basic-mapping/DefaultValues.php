<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;

#[Entity]
class Message
{
    #[Column(options: ['default' => 'Hello World!'])]
    private string $text;
}
