<?php
/**
 * Created by PhpStorm.
 * User: wouter
 * Date: 11/18/15
 * Time: 1:00 PM
 */

namespace Doctrine\Tests\Models\EagerBatched;
use Doctrine\ORM\Mapping\Table;

/**
 * Class Article
 * @package Doctrine\Tests\Models\EagerBatched
 *
 * @Entity()
 * @Table(name="eager_batched_article")
 */
class Article {

	/**
	 * @Id @Column(type="integer", name="id") @GeneratedValue
	 */
	protected $id;

	/**
	 * @Column(type="string")
	 */
	protected $title;

	/**
	 * @OneToMany(targetEntity="Tag",mappedBy="article",cascade={"persist"},fetch="EAGER_BATCHED")
	 */
	protected $tags;

	/**
	 * Get id
	 *
	 * @return mixed
	 */
	public function getId() {
		return $this->id;
	}

	/**
	 * Set id
	 *
	 * @param mixed $id
	 * @return $this
	 */
	public function setId($id) {
		$this->id = $id;

		return $this;
	}

	/**
	 * Get title
	 *
	 * @return mixed
	 */
	public function getTitle() {
		return $this->title;
	}

	/**
	 * Set title
	 *
	 * @param mixed $title
	 * @return $this
	 */
	public function setTitle($title) {
		$this->title = $title;

		return $this;
	}

	/**
	 * Get tags
	 *
	 * @return mixed
	 */
	public function getTags() {
		return $this->tags;
	}

	/**
	 * Set tags
	 *
	 * @param mixed $tags
	 * @return $this
	 */
	public function setTags($tags) {
		$this->tags = $tags;

		return $this;
	}

}