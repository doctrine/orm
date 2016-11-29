<?php

namespace Doctrine\Tests\Models\DDC4006;

/**
 * @Entity
 */
class DDC4006User
{
    /**
     * @Embedded(class="DDC4006UserId")
     */
    private $id;
}
