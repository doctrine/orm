<?php

namespace Doctrine\Tests\Models\DDC3597;

use Doctrine\ORM\Annotation as ORM;

/**
 * Description of Root
 *
 * @ORM\Entity
 *
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="discriminator", type="string")
 * @ORM\DiscriminatorMap({ "image" = "DDC3597Image"})
 * @ORM\HasLifecycleCallbacks
 */
abstract class DDC3597Root
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @var \DateTime
     * @ORM\Column(name="created_at", type="datetime", nullable=false)
     */
    protected $createdAt = null;

    /**
     * @var \DateTime
     * @ORM\Column(name="updated_at", type="datetime", nullable=false)
     */
    protected $updatedAt = null;

    /**
     * Set createdAt
     *
     * @ORM\PrePersist
     */
    public function prePersist() {
        $this->updatedAt = $this->createdAt = new \DateTime();
    }

    /**
     * Set updatedAt
     *
     * @ORM\PreUpdate
     */
    public function preUpdate() {
        $this->updatedAt = new \DateTime();
    }

    /**
     * @return int
     */
    public function getId() {
        return (int)$this->id;
    }


    /**
     * @return \DateTime
     */
    public function getCreatedAt() {
        return $this->createdAt;
    }

    /**
     * @return \DateTime
     */
    public function getUpdatedAt() {
        return $this->updatedAt;
    }
}
