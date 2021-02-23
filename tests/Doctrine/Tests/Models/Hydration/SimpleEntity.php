<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\Hydration;

use Doctrine\ORM\Annotation as ORM;

/** @ORM\Entity */
class SimpleEntity
{
    /** @ORM\Id @ORM\Column(type="integer") @ORM\GeneratedValue(strategy="AUTO") */
    public $id;
}
