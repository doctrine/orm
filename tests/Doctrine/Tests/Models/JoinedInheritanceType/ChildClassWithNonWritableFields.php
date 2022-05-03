<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\JoinedInheritanceType;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;

/**
 * @Entity
 */
class ChildClassWithNonWritableFields extends ChildClass
{
    /**
     * @var string
     * @Column(type="string", insertable=false, options={"default": "1234"})
     */
    public $nonInsertableContent;

    /**
     * @var string
     * @Column(type="string", updatable=false)
     */
    public $nonUpdatableContent;

    /**
     * @var string
     * @Column(type="integer", insertable=false, updatable=false, options={"default": 5678})
     */
    public $nonWritableContent;

    /**
     * @var string
     * @Column(type="string", insertable=true, updatable=true)
     */
    public $writableContent;
}
