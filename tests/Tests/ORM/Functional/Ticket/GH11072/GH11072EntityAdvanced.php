<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket\GH11072;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;

#[Entity]
class GH11072EntityAdvanced
{
    /** @var int */
    #[Id]
    #[Column(type: 'integer')]
    #[GeneratedValue]
    public $id;

    #[Column(type: 'json')]
    public mixed $anything;

    #[Column(type: 'json')]
    public true $alwaysTrue = true;

    #[Column(type: 'json')]
    public false $alwaysFalse = false;

    #[Column(type: 'json')]
    public null $alwaysNull = null;
}
