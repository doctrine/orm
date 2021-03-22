<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\DDC3231;

/**
 * @Entity(repositoryClass="DDC3231User2Repository")
 * @Table(name="users2")
 */
class DDC3231User2
{
    /**
     * @var int
     * @Id
     * @Column(type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     * @Column(type="string", length=255)
     */
    protected $name;
}
