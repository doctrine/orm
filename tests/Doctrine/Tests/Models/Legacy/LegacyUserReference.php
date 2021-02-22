<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\Legacy;

use DateTime;

/**
 * @Entity
 * @Table(name="legacy_users_reference")
 */
class LegacyUserReference
{
    /**
     * @var LegacyUser
     * @Id
     * @ManyToOne(targetEntity="LegacyUser", inversedBy="references")
     * @JoinColumn(name="iUserIdSource", referencedColumnName="iUserId")
     */
    private $_source;

    /**
     * @var LegacyUser
     * @Id
     * @ManyToOne(targetEntity="LegacyUser")
     * @JoinColumn(name="iUserIdTarget", referencedColumnName="iUserId")
     */
    private $_target;

    /**
     * @var string
     * @column(type="string", name="description")
     */
    private $_description;

    /**
     * @var DateTime
     * @Column(type="datetime", name="created")
     */
    private $created;

    public function __construct(LegacyUser $source, LegacyUser $target, string $description)
    {
        $source->addReference($this);
        $target->addReference($this);

        $this->_source      = $source;
        $this->_target      = $target;
        $this->_description = $description;
        $this->created      = new DateTime('now');
    }

    public function source(): LegacyUser
    {
        return $this->_source;
    }

    public function target(): LegacyUser
    {
        return $this->_target;
    }

    public function setDescription(string $desc): void
    {
        $this->_description = $desc;
    }

    public function getDescription(): string
    {
        return $this->_description;
    }
}
