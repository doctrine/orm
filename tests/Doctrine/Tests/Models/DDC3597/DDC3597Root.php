<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\DDC3597;

use DateTime;
use Doctrine\ORM\Annotation as ORM;

/**
 * Description of Root
 *
 * @ORM\Entity
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="discriminator", type="string")
 * @ORM\DiscriminatorMap({ "image" = DDC3597Image::class})
 * @ORM\HasLifecycleCallbacks
 */
abstract class DDC3597Root
{
    /**
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     *
     * @var int
     */
    protected $id;

    /**
     * @ORM\Column(name="created_at", type="datetime", nullable=false)
     *
     * @var DateTime
     */
    protected $createdAt;

    /**
     * @ORM\Column(name="updated_at", type="datetime", nullable=false)
     *
     * @var DateTime
     */
    protected $updatedAt;

    /**
     * Set createdAt
     *
     * @ORM\PrePersist
     */
    public function prePersist()
    {
        $this->updatedAt = $this->createdAt = new DateTime();
    }

    /**
     * Set updatedAt
     *
     * @ORM\PreUpdate
     */
    public function preUpdate()
    {
        $this->updatedAt = new DateTime();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return (int) $this->id;
    }


    /**
     * @return DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @return DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }
}
