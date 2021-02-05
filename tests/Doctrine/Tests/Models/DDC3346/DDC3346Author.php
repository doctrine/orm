<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\DDC3346;

use Doctrine\Common\Collections\Collection;

/**
 * @Entity
 * @Table(name="ddc3346_users")
 */
class DDC3346Author
{
    /**
     * @var int
     * @Id @Column(type="integer")
     * @GeneratedValue
     */
    public $id;

    /**
     * @var string
     * @Column(type="string", length=255, unique=true)
     */
    public $username;

    /**
     * @var Collection<int, DDC3346Article>
     * @OneToMany(targetEntity="DDC3346Article", mappedBy="user", fetch="EAGER", cascade={"detach"})
     */
    public $articles = [];
}
