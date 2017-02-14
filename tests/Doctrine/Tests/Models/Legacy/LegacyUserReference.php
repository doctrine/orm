<?php

namespace Doctrine\Tests\Models\Legacy;

use Doctrine\ORM\Annotation as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="legacy_users_reference")
 */
class LegacyUserReference
{
    /**
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="LegacyUser", inversedBy="references")
     * @ORM\JoinColumn(name="iUserIdSource", referencedColumnName="iUserId")
     */
    private $source;

    /**
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="LegacyUser")
     * @ORM\JoinColumn(name="iUserIdTarget", referencedColumnName="iUserId")
     */
    private $target;

    /**
     * @ORM\Column(type="string", name="description")
     */
    private $description;

    /**
     * @ORM\Column(type="datetime", name="created")
     */
    private $created;

    public function __construct(LegacyUser $source, LegacyUser $target, $description)
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
