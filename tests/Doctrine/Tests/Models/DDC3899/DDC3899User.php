<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\DDC3899;

use Doctrine\Common\Collections\Collection;

/**
 * @Entity
 * @Table(name="dc3899_users")
 */
class DDC3899User
{
    /**
     * @var int
     * @Id
     * @Column(type="integer")
     */
    public $id;

    /**
     * @psalm-var Collection<int, DDC3899Contract>
     * @OneToMany(targetEntity="DDC3899Contract", mappedBy="user")
     */
    public $contracts;
}
