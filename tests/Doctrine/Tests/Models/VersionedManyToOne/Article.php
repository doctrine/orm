<?php

namespace Doctrine\Tests\Models\VersionedManyToOne;

use Doctrine\ORM\Annotation as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="versioned_many_to_one_article")
 */
class Article
{
    /**
     * @ORM\Id
     * @ORM\Column(name="id", type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    public $id;

    /**
     * @ORM\Column(name="name")
     */
    public $name;

    /**
     * @ORM\ManyToOne(targetEntity="Category", cascade={"merge", "persist"})
     */
    public $category;

    /**
     * Version column
     *
     * @ORM\Column(type="integer", name="version")
     * @ORM\Version
     */
    public $version;
}
