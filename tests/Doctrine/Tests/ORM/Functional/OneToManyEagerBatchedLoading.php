<?php
/**
 * Created by PhpStorm.
 * User: wouter
 * Date: 11/18/15
 * Time: 12:54 PM
 */

namespace Doctrine\Tests\ORM\Functional;


use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Tests\Models\EagerBatched\Article;
use Doctrine\Tests\Models\EagerBatched\Tag;
use Doctrine\Tests\OrmFunctionalTestCase;

class OneToManyEagerBatchedLoading extends OrmFunctionalTestCase
{

	protected function setUp() {

		$this->useModelSet('eager_batched');
		parent::setUp();

		$tags = explode(' ', 'professionally fabricate initiatives before expanded array practices');

		for($i = 0; $i < 100; $i++) {

			$article = new Article();
			$article->setTitle('Test article '.$i);
			$article->setTags(array_map(function($word) use ($article) {
				return Tag::factory($article, $word);
			}, $tags));

			$this->_em->persist($article);
		}

		$this->_em->flush();
		$this->_em->clear();
	}

	public function testEagerBatchedLoading() {

		$start = microtime(true);

		$currentQueryBefore = $this->_sqlLoggerStack->currentQuery;

		$all = $this->_em->getRepository('Doctrine\Tests\Models\EagerBatched\Article')->findAll();

		/** @var Article $article */
		foreach($all as $article) {
			$this->assertEquals(7, count($article->getTags()));
		}

		// the EAGER_BATCHED mode will trigger two queries. One to select from Articles, and one to select all
		// tags that are linked to the articles we've loaded
		$this->assertEquals(2, $this->_sqlLoggerStack->currentQuery - $currentQueryBefore);

		return microtime(true) - $start;
	}


	/**
	 * @depends testEagerBatchedLoading
	 * @param $batchedLoadingTime
	 */
	public function testLazyLoading($batchedLoadingTime) {

		$start = microtime(true);

		$currentQueryBefore = $this->_sqlLoggerStack->currentQuery;
		$class = $this->_em->getClassMetadata('Doctrine\Tests\Models\EagerBatched\Article');
		$class->associationMappings['tags']['fetch'] = ClassMetadata::FETCH_LAZY;

		$all = $this->_em->getRepository('Doctrine\Tests\Models\EagerBatched\Article')->findAll();

		/** @var Article $article */
		foreach($all as $article) {
			$this->assertEquals(7, count($article->getTags()));
		}

		// Check that what we're improving is indeed behaving as we don't want
		$this->assertEquals(101, $this->_sqlLoggerStack->currentQuery - $currentQueryBefore);

		$lazyLoadingTime = microtime(true) - $start;

		$this->assertGreaterThan($batchedLoadingTime, $lazyLoadingTime, 'Make sure the improvement improves');
	}

	/**
	 * @depends testEagerBatchedLoading
	 * @param $batchedLoadingTime
	 */
	public function testEagerLoading($batchedLoadingTime) {

		$start = microtime(true);

		$currentQueryBefore = $this->_sqlLoggerStack->currentQuery;
		$class = $this->_em->getClassMetadata('Doctrine\Tests\Models\EagerBatched\Article');
		$class->associationMappings['tags']['fetch'] = ClassMetadata::FETCH_EAGER;

		$all = $this->_em->getRepository('Doctrine\Tests\Models\EagerBatched\Article')->findAll();

		/** @var Article $article */
		foreach($all as $article) {
			$this->assertEquals(7, count($article->getTags()));
		}

		// Check that what we're improving is indeed behaving as we don't want
		$this->assertEquals(1, $this->_sqlLoggerStack->currentQuery - $currentQueryBefore);

		$time = microtime(true) - $start;

		$this->assertGreaterThan($batchedLoadingTime, $time, 'Make sure the improvement improves');
	}


}