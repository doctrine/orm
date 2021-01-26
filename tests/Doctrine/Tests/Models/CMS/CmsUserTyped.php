<?php
declare(strict_types=1);

namespace Doctrine\Tests\Models\CMS;

use DateInterval;
use DateTime;
use DateTimeImmutable;

/**
 * @Entity
 * @Table(name="cms_users_typed")
 */
class CmsUserTyped
{
    /**
     * @Id @Column
     * @GeneratedValue
     */
    public int $id;
    /**
     * @Column(length=50)
     */
    public ?string $status;

    /**
     * @Column(length=255, unique=true)
     */
    public string $username;

    /**
     * @Column(type="string", length=255)
     */
    public $name;

    /**
     * @Column
     */
    public DateInterval $dateInterval;

    /**
     * @Column
     */
    public DateTime $dateTime;

    /**
     * @Column
     */
    public DateTimeImmutable $dateTimeImmutable;

    /**
     * @Column
     */
    public array $array;

    /**
     * @Column
     */
    public bool $boolean;

    /**
     * @Column
     */
    public float $float;

    /**
     * @OneToOne(cascade={"persist"}, orphanRemoval=true)
     * @JoinColumn
     */
    public CmsEmail $email;
}
