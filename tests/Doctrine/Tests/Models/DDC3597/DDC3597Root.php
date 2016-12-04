<?php

namespace Doctrine\Tests\Models\DDC3597;
use Doctrine\ORM\Mapping\DiscriminatorMap;

/**
 * Description of Root
 *
 * @Entity
 *
 * @InheritanceType("JOINED")
 * @DiscriminatorColumn(name="discriminator", type="string")
 * @DiscriminatorMap({ "image" = "DDC3597Image"})
 * @HasLifecycleCallbacks
 */
abstract class DDC3597Root {

    const CLASSNAME = __CLASS__;

    /**
     * @var int
     *
     * @Column(name="id", type="integer", nullable=false)
     * @Id
     * @GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @var \DateTimeInterface
     * @Column(name="created_at", type="datetime", nullable=false)
     */
    protected $createdAt = null;

    /**
     * @var \DateTimeInterface
     * @Column(name="updated_at", type="datetime", nullable=false)
     */
    protected $updatedAt = null;

    /**
     * Set createdAt
     *
     * @PrePersist
     */
    public function _prePersist() {
        $this->updatedAt = $this->createdAt = new \DateTime();
    }

    /**
     * Set updatedAt
     *
     * @PreUpdate
     */
    public function _preUpdate() {
        $this->updatedAt = new \DateTime();
    }

    /**
     * @return int
     */
    public function getId() {
        return (int)$this->id;
    }


    /**
     * @return \DateTimeInterface
     */
    public function getCreatedAt() {
        return $this->createdAt;
    }

    /**
     * @return \DateTimeInterface
     */
    public function getUpdatedAt() {
        return $this->updatedAt;
    }
}