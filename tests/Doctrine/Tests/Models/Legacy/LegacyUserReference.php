<?php

namespace Doctrine\Tests\Models\Legacy;

/**
 * @Entity
 * @Table(name="legacy_users_reference")
 */
class LegacyUserReference
{
    /**
     * @Id
     * @ManyToOne(targetEntity="LegacyUser", inversedBy="references")
     * @JoinColumn(name="iUserIdSource", referencedColumnName="iUserId")
     */
    private $source;

    /**
     * @Id
     * @ManyToOne(targetEntity="LegacyUser")
     * @JoinColumn(name="iUserIdTarget", referencedColumnName="iUserId")
     */
    private $target;

    /**
     * @column(type="string", name="description")
     */
    private $description;

    /**
     * @column(type="datetime", name="created")
     */
    private $created;

    public function __construct($source, $target, $description)
    {
        $source->addReference($this);
        $target->addReference($this);

        $this->source = $source;
        $this->target = $target;
        $this->description = $description;
        $this->created = new \DateTime("now");
    }

    public function source()
    {
        return $this->source;
    }

    public function target()
    {
        return $this->target;
    }

    public function setDescription($desc)
    {
        $this->description = $desc;
    }

    public function getDescription()
    {
        return $this->description;
    }
}
