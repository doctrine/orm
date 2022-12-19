<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\GH10097;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Entity;

/** @Entity */
class EntityWithReadonlyIdentifier
{
    /**
     * @ORM\Id
     * @ORM\Column
     * @ORM\GeneratedValue
     */
    public readonly int $id;
}
