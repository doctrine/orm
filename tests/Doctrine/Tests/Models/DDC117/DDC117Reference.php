<?php

namespace Doctrine\Tests\Models\DDC117;

use Doctrine\ORM\Annotation as ORM;

/**
 * @ORM\Entity
 */
class DDC117Reference
{
    /**
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="DDC117Article", inversedBy="references")
     * @ORM\JoinColumn(name="source_id", referencedColumnName="article_id")
     */
    private $source;

    /**
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="DDC117Article")
     * @ORM\JoinColumn(name="target_id", referencedColumnName="article_id")
     */
    private $target;

    /**
     * @ORM\Column(type="string")
     */
    private $description;

    /**
     * @ORM\Column(type="datetime")
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
