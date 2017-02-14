<?php

namespace Doctrine\Tests\Models\NullDefault;

use Doctrine\ORM\Annotation as ORM;

/** @ORM\Entity */
class NullDefaultColumn
{
    /** @ORM\Id @ORM\GeneratedValue @ORM\Column(type="integer") */
    public $id;

    /** @ORM\Column(options={"default":NULL}) */
    public $nullDefault;
}
