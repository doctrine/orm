<?php

namespace Doctrine\Tests\Models\DDC117;

use Doctrine\ORM\Annotation as ORM;

/**
 * Foreign Key Entity without additional fields!
 *
 * @ORM\Entity
 */
class DDC117Link
{
    /**
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="DDC117Article", inversedBy="links")
     * @ORM\JoinColumn(name="source_id", referencedColumnName="article_id")
     */
    public $source;

    /**
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="DDC117Article")
     * @ORM\JoinColumn(name="target_id", referencedColumnName="article_id")
     */
    public $target;

    public function __construct($source, $target, $description)
    {
        $this->source = $source;
        $this->target = $target;
    }
}
