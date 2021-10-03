<?php

declare(strict_types=1);

namespace Doctrine\Tests\Examples;

use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimezone;

/**
 * @\Doctrine\ORM\Mapping\Entity
 * @\Doctrine\ORM\Mapping\HasLifecycleCallbacks
 */
class DateTimeInstance
{
    /**
     * @\Doctrine\ORM\Mapping\Id
     * @\Doctrine\ORM\Mapping\Column(type="integer")
     */
    private $id;

    /**
     * @\Doctrine\ORM\Mapping\Column(type="datetime")
     * @var DateTimeImmutable
     */
    private $eventDateTime;

    /** @\Doctrine\ORM\Mapping\Column(type="string") */
    private $timezone;

    public function __construct(DateTimeInterface $eventDateTime, int $id)
    {
        $this->id = $id;
        $this->eventDateTime = $eventDateTime;
        $this->timezone = $eventDateTime->getTimeZone()->getName();
    }

    /** @\Doctrine\ORM\Mapping\PostLoad */
    public function correctTimezone(): void
    {
        $correctEntity = new DateTimeImmutable(
            $this->eventDateTime->format('Y-m-d H:i:s'),
            new DateTimeZone($this->timezone)
        );

        $this->eventDateTime->setTimezone(new DateTimezone($this->timezone))->modify($correctEntity->format('Y-m-d H:i:s'));
    }

    public function convertToUtc(): void
    {
        $this->eventDateTime = clone $this->eventDateTime->setTimezone(new DateTimeZone('UTC'));
    }

    public function getEventDateTime(): DateTimeImmutable
    {
        return DateTimeImmutable::createFromMutable($this->eventDateTime);
    }
}