<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket\GH11072;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;

#[Entity]
class GH11072EntityBasic
{
    /** @var int */
    #[Id]
    #[Column(type: 'integer')]
    #[GeneratedValue]
    public $id;

    #[Column(type: 'json')]
    public string $jsonString = 'test';

    #[Column(type: 'json')]
    public int $age = 99;

    #[Column(type: 'json')]
    public float $score = 0.0;

    #[Column(type: 'json', nullable: true)]
    public bool|null $trinary = null;

    #[Column(type: 'json')]
    public array $metadata = [];
}
