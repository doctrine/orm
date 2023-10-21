<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\Rating;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\Table;

/**
 * @Entity
 * @Table(name="ratings_review")
 */
class Review
{
    /**
     * @Id
     * @Column(type="string")
     */
    private string $id;
    /**
     * @Column(type="string", length=50, nullable=true)
     */
    private string $text;
    /**
     * @Column(type="integer", nullable=false)
     */
    private int $rating;
    /**
     * why isnt , orphanRemoval=true allowed here
     *
     * @ManyToOne(targetEntity="User", cascade={"all"})
     * @JoinColumn(name="user_id", referencedColumnName="id")
     */
    private User $user;

    /**
     * Forced by doctrine
     *
     * @var Business
     */
    private $business;

    public function __construct(
        string $id,
        string $text,
        int $rating,
        User $user
    ) {
        $this->id     = $id;
        $this->text   = $text;
        $this->rating = $rating;
        $this->user   = $user;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getText(): string
    {
        return $this->text;
    }

    public function getRating(): int
    {
        return $this->rating;
    }

    public function setText(string $text): void
    {
        $this->text = $text;
    }

    public function setRating(int $rating): void
    {
        $this->rating = $rating;
    }

    public function setBusiness(Business $business): void
    {
        $this->business = $business;
    }

    public function getBusiness(): Business
    {
        return $this->business;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): void
    {
        $this->user = $user;
    }
}
