<?php

namespace Doctrine\Tests\Models\Generic;

use Doctrine\ORM\Annotation as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="date_time_model")
 */
class DateTimeModel
{
    /**
     * @ORM\Id @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     */
    public $id;
    /**
     * @ORM\Column(name="col_datetime", type="datetime", nullable=true)
     */
    public $datetime;
    /**
     * @ORM\Column(name="col_date", type="date", nullable=true)
     */
    public $date;
    /**
     * @ORM\Column(name="col_time", type="time", nullable=true)
     */
    public $time;
}
