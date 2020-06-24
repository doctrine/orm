<?php

namespace Doctrine\Tests\Models\GH8193;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

/**
 * @Entity
 * @Table(name = "users")
 */
class User
{
    /**
     * @var int
     *
     * @Id
     * @Column(type = "integer", options = {"unsigned" = true})
     * @GeneratedValue
     */
    private $userId;

    /**
     * @var string
     *
     * @Column(type = "string", length = 255)
     */
    private $email;

    /**
     * @var Event[]|Collection
     *
     * @OneToMany(targetEntity = "Event", mappedBy = "user")
     */
    private $events;

    public function __construct()
    {
        $this->events = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->userId;
    }

    public function setEmail(?string $email = null): self
    {
        $this->email = $email;
        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function addEvent(Event $event): self
    {
        $this->events->add($event);
        return $this;
    }

    public function removeEvent(Event $event): self
    {
        $this->events->removeElement($event);
        return $this;
    }

    /** @return Event[] */
    public function getEvents(): Collection
    {
        return $this->events;
    }
}
