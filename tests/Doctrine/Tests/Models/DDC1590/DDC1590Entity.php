<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\DDC1590;

use DateTime;

/**
 * @Entity
 * @MappedSuperClass
 */
abstract class DDC1590Entity
{
    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /** @Column(type="datetime") */
    protected $created_at;

    /**
     * Get id
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Set createdAt
     */
    public function setCreatedAt(DateTime $createdAt): DDC1590User
    {
        $this->created_at = $createdAt;

        return $this;
    }

    /**
     * Get createdAt
     */
    public function getCreatedAt(): DateTime
    {
        return $this->created_at;
    }
}
