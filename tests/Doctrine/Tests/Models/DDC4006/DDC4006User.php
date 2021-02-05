<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\DDC4006;

/**
 * @Entity
 */
class DDC4006User
{
    /** @Embedded(class="DDC4006UserId") */
    private $id;
}
