<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\DDC1872;

/**
 * @Entity
 */
class DDC1872ExampleEntityWithoutOverride
{
    use DDC1872ExampleTrait;
}
