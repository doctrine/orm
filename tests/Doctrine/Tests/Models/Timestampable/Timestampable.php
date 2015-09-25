<?php

namespace Doctrine\Tests\Models\Timestampable;

/**
 * Trait Timestampable
 *
 * @MappedSuperclass()
 * @HasLifecycleCallbacks()
 */
trait Timestampable
{
    /**
     * @Column(type="datetime")
     *
     * @var \DateTime
     */
    protected $created;

    /**
     * @Column(type="datetime")
     *
     * @var \DateTime
     */
    protected $updated;

    /**
     * @return \DateTime
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * @return \DateTime
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    /**
     * @PrePersist
     */
    public function onCreate()
    {
        $this->created = $this->updated = new \DateTime('now');
    }

    /**
     * @PreUpdate
     */
    public function onUpdate()
    {
        $this->updated = new \DateTime('now');
    }
}