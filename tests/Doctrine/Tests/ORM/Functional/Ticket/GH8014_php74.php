<?php

namespace Doctrine\Test\ORM\Functional\Ticket;

/**
 * @Entity()
 */
class GH8014Foo
{
    /**
     * @var integer
     *
     * @Id()
     * @Column(name="id", type="integer")
     * @GeneratedValue(strategy="IDENTITY")
     */
    public $id;

    /**
     * @Embedded(class="Doctrine\Test\ORM\Functional\Ticket\GH8014Bar")
     */
    public ?GH8014Bar $bar = null;
}

/**
 * @Embeddable()
 */
class GH8014Bar
{
    /**
     * @Column(type="datetime_immutable", nullable=true)
     */
    public DateTimeImmutable $startDate;
}
