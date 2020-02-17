<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\DDC4006;

use Doctrine\ORM\Annotation as ORM;

/**
 * @ORM\Embeddable
 */
class DDC4006UserId
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue("IDENTITY")
     * @ORM\Column(type="integer")
     */
    private $id;
}
