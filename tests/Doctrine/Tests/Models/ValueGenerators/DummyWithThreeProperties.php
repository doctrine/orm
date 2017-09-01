<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\ValueGenerators;

use Doctrine\ORM\Annotation as ORM;

/**
 * @ORM\Entity
 */
class DummyWithThreeProperties
{
    private $a;

    private $b;

    private $c;
}
