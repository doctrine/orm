<?php
/**
 * Created by PhpStorm.
 * User: wouter
 * Date: 11/18/15
 * Time: 1:00 PM
 */

namespace Doctrine\Tests\Models\EagerBatched;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Table;
use Doctrine\Tests\Models\EagerBatched\Tag;

/**
 * Class Article
 * @package Doctrine\Tests\Models\EagerBatched
 *
 * @Entity()
 * @Table(name="eager_batched_article")
 */
class Article
{

    /**
     * @Id @Column(type="integer", name="id") @GeneratedValue
     * @var int
     */
    protected $id;

    /**
     * @Column(type="string")
     * @var string|null
     */
    protected $title;

    /**
     * @OneToMany(targetEntity="Tag",mappedBy="article",cascade={"persist"},fetch="EAGER_BATCHED")
     * @var Tag[]|Collection
     */
    protected $tags;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set id
     *
     * @param integer $id
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get title
     *
     * @return string|null
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set title
     *
     * @param string $title
     * @return $this
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get tags
     *
     * @return Collection|Tag[]
     */
    public function getTags()
    {
        return $this->tags;
    }

    /**
     * Set tags
     *
     * @param Collection|Tag[] $tags
     * @return $this
     */
    public function setTags($tags)
    {
        $this->tags = $tags;

        return $this;
    }

}