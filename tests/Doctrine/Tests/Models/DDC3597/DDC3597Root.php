<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\DDC3597;

use DateTime;
use Doctrine\ORM\Mapping\DiscriminatorMap;

/**
 * Description of Root
 *
 * @Entity
 * @InheritanceType("JOINED")
 * @DiscriminatorColumn(name="discriminator", type="string")
 * @DiscriminatorMap({ "image" = "DDC3597Image"})
 * @HasLifecycleCallbacks
 */
abstract class DDC3597Root
{
    /**
     * @var int
     * @Column(name="id", type="integer", nullable=false)
     * @Id
     * @GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @var DateTime
     * @Column(name="created_at", type="datetime", nullable=false)
     */
    protected $createdAt = null;

    /**
     * @var DateTime
     * @Column(name="updated_at", type="datetime", nullable=false)
     */
    protected $updatedAt = null;

    /**
     * Set createdAt
     *
     * @PrePersist
     */
    public function _prePersist(): void
    {
        $this->updatedAt = $this->createdAt = new DateTime();
    }

    /**
     * Set updatedAt
     *
     * @PreUpdate
     */
    public function _preUpdate(): void
    {
        $this->updatedAt = new DateTime();
    }

    public function getId(): int
    {
        return (int) $this->id;
    }

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): DateTime
    {
        return $this->updatedAt;
    }
}
