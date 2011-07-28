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
     * @ManyToOne(targetEntity="LegacyUser", inversedBy="_references")
     * @JoinColumn(name="iUserIdSource", referencedColumnName="iUserId")
     */
    private $_source;

    /**
     * @Id
     * @ManyToOne(targetEntity="LegacyUser", inversedBy="_references")
     * @JoinColumn(name="iUserIdTarget", referencedColumnName="iUserId")
     */
    private $_target;

    /**
     * @column(type="string")
     */
    private $_description;

    /**
     * @column(type="datetime")
     */
    private $_created;

    public function __construct($source, $target, $description)
    {
        $source->addReference($this);
        $target->addReference($this);

        $this->_source = $source;
        $this->_target = $target;
        $this->_description = $description;
        $this->_created = new \DateTime("now");
    }

    public function source()
    {
        return $this->_source;
    }

    public function target()
    {
        return $this->_target;
    }

    public function setDescription($desc)
    {
        $this->_description = $desc;
    }

    public function getDescription()
    {
        return $this->_description;
    }
}
