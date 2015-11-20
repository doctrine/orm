<?php
/**
 * Created by PhpStorm.
 * User: wouter
 * Date: 11/18/15
 * Time: 1:00 PM
 */

namespace Doctrine\Tests\Models\EagerBatched;

/**
 * Class Tag
 * @package Doctrine\Tests\Models\EagerBatched
 *
 * @Entity()
 * @Table(name="eager_batched_tag")
 */
class Tag
{

	/**
	 * @Column(type="string")
	 * @Id()
	 */
	protected $name;

	public static function factory(Article $a, $tag) {
		$me = new static();
		$me->setArticle($a);
		$me->setName($tag);
		return $me;
	}

	/**
	 * @ManyToOne(targetEntity="Article",cascade={"persist"},fetch="LAZY",inversedBy="tags")
	 * @Id()
	 */
	protected $article;

	public function __toString() {
		return $this->name;
	}

	/**
	 * Get name
	 *
	 * @return mixed
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * Set name
	 *
	 * @param mixed $name
	 * @return $this
	 */
	public function setName($name) {
		$this->name = $name;

		return $this;
	}

	/**
	 * Get article
	 *
	 * @return Article
	 */
	public function getArticle() {
		return $this->article;
	}

	/**
	 * Set article
	 *
	 * @param mixed $article
	 * @return $this
	 */
	public function setArticle($article) {
		$this->article = $article;

		return $this;
	}


}