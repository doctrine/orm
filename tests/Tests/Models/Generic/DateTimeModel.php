<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\Generic;

use DateTimeImmutable;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Table;

#[Table(name: 'date_time_model')]
#[Entity]
class DateTimeModel
{
    /** @var int */
    #[Id]
    #[Column(type: 'integer')]
    #[GeneratedValue]
    public $id;

    /** @var DateTimeImmutable|null */
    #[Column(name: 'col_datetime', type: 'datetime_immutable', nullable: true)]
    public $datetime;

    /** @var DateTimeImmutable|null */
    #[Column(name: 'col_date', type: 'date_immutable', nullable: true)]
    public $date;

    /** @var DateTimeImmutable|null */
    #[Column(name: 'col_time', type: 'time_immutable', nullable: true)]
    public $time;
}
