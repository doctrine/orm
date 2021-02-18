<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\TypedProperties;

use DateInterval;
use DateTime;
use DateTimeImmutable;
use Doctrine\Tests\Models\CMS\CmsEmail;

/**
 * @Entity
 * @Table(name="cms_users_typed")
 */
class UserTyped
{
    /**
     * @Id @Column
     * @GeneratedValue
     */
    public int $id;
    /** @Column(length=50) */
    public ?string $status;

    /** @Column(length=255, unique=true) */
    public string $username;

    /** @Column */
    public DateInterval $dateInterval;

    /** @Column */
    public DateTime $dateTime;

    /** @Column */
    public DateTimeImmutable $dateTimeImmutable;

    /** @Column */
    public array $array;

    /** @Column */
    public bool $boolean;

    /** @Column */
    public float $float;

    /**
     * @OneToOne(cascade={"persist"}, orphanRemoval=true)
     * @JoinColumn
     */
    public CmsEmail $email;
}
