<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Embeddable;
use Doctrine\ORM\Mapping\Embedded;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;

/**
 * This file is required to be in a separate file to allow using PHP 8.1 features which will otherwise break tests against PHP 8.0 or lower
 */

abstract class GH10049AggregatedRootId
{
    /**
     * @Id
     * @Column(name="id", type="string")
     */
    public readonly string $value;

    public function __construct(?string $value = null)
    {
        $this->value = $value ?? 'a';
    }
}

/**
 * @Embeddable
 */
final class GH10049BookId extends GH10049AggregatedRootId
{
}

/**
 * @Entity
 */
class GH10049Book
{
    /** @Embedded(columnPrefix=false) */
    public readonly GH10049BookId $id;

    public function __construct(?GH10049BookId $id = null)
    {
        $this->id = $id ?? new GH10049BookId();
    }
}
