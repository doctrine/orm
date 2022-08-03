<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\DDC117;

use DateTime;

/**
 * @Entity
 */
class DDC117Reference
{
    /**
     * @var DDC117Article
     * @Id
     * @ManyToOne(targetEntity="DDC117Article", inversedBy="references")
     * @JoinColumn(name="source_id", referencedColumnName="article_id")
     */
    private $source;

    /**
     * @var DDC117Article
     * @Id
     * @ManyToOne(targetEntity="DDC117Article")
     * @JoinColumn(name="target_id", referencedColumnName="article_id")
     */
    private $target;

    /**
     * @var string
     * @column(type="string")
     */
    private $description;

    /**
     * @var DateTime
     * @column(type="datetime")
     */
    private $created;

    public function __construct(DDC117Article $source, DDC117Article $target, string $description)
    {
        $source->addReference($this);
        $target->addReference($this);

        $this->source      = $source;
        $this->target      = $target;
        $this->description = $description;
        $this->created     = new DateTime('now');
    }

    public function source(): DDC117Article
    {
        return $this->source;
    }

    public function target(): DDC117Article
    {
        return $this->target;
    }

    public function setDescription(string $desc): void
    {
        $this->description = $desc;
    }

    public function getDescription(): string
    {
        return $this->description;
    }
}
