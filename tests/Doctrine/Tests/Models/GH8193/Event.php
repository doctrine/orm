<?php

namespace Doctrine\Tests\Models\GH8193;

/**
 * @Entity
 * @Table(name = "events")
 */
class Event
{
    /**
     * @var int
     *
     * @Id
     * @Column(type = "integer", options = {"unsigned" = true})
     * @GeneratedValue
     */
    private $eventId;

    /**
     * @var float
     *
     * @Column(type = "decimal", precision = 10, scale = 2, options = {"default" = 0})
     */
    private $amount;

    /**
     * @var User
     *
     * @ManyToOne(targetEntity = "User", inversedBy = "events")
     * @JoinColumn(name = "userId", referencedColumnName = "userId", nullable = false)
     */
    private $user;

    public function getId(): ?int
    {
        return $this->eventId;
    }

    public function setAmount(float $amount): self
    {
        $this->amount = $amount;
        return $this;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;
        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }
}
