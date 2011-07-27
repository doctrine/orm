<?php

namespace Doctrine\Tests\Models\DDC117;

/**
 * Foreign Key Entity without additional fields!
 *
 * @Entity
 */
class DDC117Link
{
    /**
     * @Id
     * @ManyToOne(targetEntity="DDC117Article", inversedBy="links")
     * @JoinColumn(name="source_id", referencedColumnName="article_id")
     */
    public $source;

    /**
     * @Id
     * @ManyToOne(targetEntity="DDC117Article")
     * @JoinColumn(name="target_id", referencedColumnName="article_id")
     */
    public $target;

    public function __construct($source, $target, $description)
    {
        $this->source = $source;
        $this->target = $target;
    }
}