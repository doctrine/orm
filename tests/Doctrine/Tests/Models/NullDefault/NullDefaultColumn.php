<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\NullDefault;

/** @Entity */
class NullDefaultColumn
{
    /**
     * @var int
     * @Id
     * @GeneratedValue
     * @Column(type="integer")
     */
    public $id;

    /**
     * @var mixed
     * @Column(options={"default":NULL})
     */
    public $nullDefault;
}
