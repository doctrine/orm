<?php

declare(strict_types=1);

namespace App\Entity;

use DateTime;
use Doctrine\DBAL\Schema\DefaultExpression\CurrentTimestamp;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;

#[Entity]
class Message
{
    #[Column(options: ['default' => 'Hello World!'])]
    private string $text;

    #[Column(options: ['default' => new CurrentTimestamp()], insertable: false, updatable: false)]
    private DateTime $createdAt;
}
