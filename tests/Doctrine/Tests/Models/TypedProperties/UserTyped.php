<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\TypedProperties;

use Doctrine\ORM\Mapping as ORM;

use DateInterval;
use DateTime;
use DateTimeImmutable;
use Doctrine\Tests\Models\CMS\CmsEmail;

/**
 * @Entity
 * @Table(name="cms_users_typed")
 */
#[ORM\Entity, ORM\Table(name: "cms_users_typed")]
class UserTyped
{
    /**
     * @Id @Column
     * @GeneratedValue
     */
    #[ORM\Id, ORM\Column, ORM\GeneratedValue]
    public int $id;
    /** @Column(length=50) */
    #[ORM\Column(length: 50)]
    public ?string $status;

    /** @Column(length=255, unique=true) */
    #[ORM\Column(length: 255, unique: true)]
    public string $username;

    /** @Column */
    #[ORM\Column]
    public DateInterval $dateInterval;

    /** @Column */
    #[ORM\Column]
    public DateTime $dateTime;

    /** @Column */
    #[ORM\Column]
    public DateTimeImmutable $dateTimeImmutable;

    /** @Column */
    #[ORM\Column]
    public array $array;

    /** @Column */
    #[ORM\Column]
    public bool $boolean;

    /** @Column */
    #[ORM\Column]
    public float $float;

    /**
     * @OneToOne(cascade={"persist"}, orphanRemoval=true)
     * @JoinColumn
     */
    #[ORM\OneToOne(cascade: ["persist"], orphanRemoval: true), ORM\JoinColumn]
    public CmsEmail $email;
}
