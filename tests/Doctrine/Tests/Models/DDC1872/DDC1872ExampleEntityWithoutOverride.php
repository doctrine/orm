<?php

namespace Doctrine\Tests\Models\DDC1872;

use Doctrine\ORM\Annotation as ORM;

/**
 * @ORM\Entity
 */
class DDC1872ExampleEntityWithoutOverride
{
    use DDC1872ExampleTrait;
}
