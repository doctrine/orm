<?php
declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\DBAL\Schema\SchemaException;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\Cache;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\OneToOne;

/**
 */
class DDC6470Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
	public function setUp()
	{
		$this->enableSecondLevelCache();
		parent::setUp();

		try {
			$this->setUpEntitySchema([
				DDC6470Source::class,
				DDC6470Target::class,
			]);
		} catch (SchemaException $e) {
		}
	}

	/**
	 * @throws \Doctrine\Common\Persistence\Mapping\MappingException
	 * @throws \Doctrine\ORM\ORMException
	 * @throws \Doctrine\ORM\OptimisticLockException
	 */
	public function testOneToOne()
	{
		$source = new DDC6470Source();
		$target = new DDC6470Target();
		$source->setTarget($target);

		$this->_em->persist($source);
		$this->_em->flush();
		$this->_em->clear();

		$this->assertTrue($this->_em->getCache()->containsEntity(DDC6470Source::class, ['id' => $source->getId()]));
		$this->assertTrue($this->_em->getCache()->containsEntity(DDC6470Target::class, ['id' => $target->getId()]));

		$queryCount = $this->getCurrentQueryCount();

		/** @var EntityRepository $er */
		$er = $this->_em->getRepository(DDC6470Source::class);
		$qb = $er->createQueryBuilder("n");
		$qb->setCacheable(true)->setLifetime(3 * 60)->setCacheRegion($region = time());

		$result = $qb->getQuery()->getResult();

		$newQueryCount = $this->getCurrentQueryCount();
		$this->assertEquals($queryCount + 2, $newQueryCount, "Assert everything get from cache + 1 request for query");

		/** @var EntityRepository $er */
		$er = $this->_em->getRepository(DDC6470Source::class);
		$qb = $er->createQueryBuilder("n");
		$qb->setCacheable(true)->setLifetime(3 * 60)->setCacheRegion($region);
		$qb->getQuery()->getResult();
		$this->assertEquals($newQueryCount, $this->getCurrentQueryCount(), "Assert everything get from cache");
	}
}

/**
 * @Entity()
 * @Cache(usage="NONSTRICT_READ_WRITE")
 */
class DDC6470Target
{
	/**
	 * @Id
	 * @GeneratedValue()
	 * @Column(type="integer")
	 * @var int
	 */
	protected $id;
	/**
	 * @Cache("NONSTRICT_READ_WRITE")
	 * @OneToOne(targetEntity="Doctrine\Tests\ORM\Functional\Ticket\DDC6470Source", inversedBy="target")
	 * @var DDC6470Source
	 */
	protected $source;

	/**
	 * @return int
	 */
	public function getId(): ?int
	{
		return $this->id;
	}

	/**
	 * @param int $id
	 * @return DDC6470Target
	 */
	public function setId(?int $id): DDC6470Target
	{
		$this->id = $id;

		return $this;
	}

	/**
	 * @return DDC6470Source
	 */
	public function getSource(): ?DDC6470Source
	{
		return $this->source;
	}

	/**
	 * @param DDC6470Source $source
	 * @return DDC6470Target
	 */
	public function setSource(?DDC6470Source $source): DDC6470Target
	{
		$this->source = $source;

		return $this;
	}

}

/**
 * @Entity
 * @Cache(usage="NONSTRICT_READ_WRITE")
 */
class DDC6470Source
{
	/**
	 * @Id
	 * @GeneratedValue()
	 * @Column(type="integer")
	 * @var int
	 */
	protected $id;
	/**
	 * @Cache("NONSTRICT_READ_WRITE")
	 * @OneToOne(targetEntity="Doctrine\Tests\ORM\Functional\Ticket\DDC6470Target", mappedBy="source", cascade={"persist"})
	 * @var DDC6470Target
	 */
	protected $target;

	/**
	 * @return int
	 */
	public function getId(): ?int
	{
		return $this->id;
	}

	/**
	 * @param int $id
	 * @return DDC6470Source
	 */
	public function setId(?int $id): DDC6470Source
	{
		$this->id = $id;

		return $this;
	}

	/**
	 * @return DDC6470Target
	 */
	public function getTarget(): ?DDC6470Target
	{
		return $this->target;
	}

	/**
	 * @param DDC6470Target $target
	 * @return DDC6470Source
	 */
	public function setTarget(?DDC6470Target $target): DDC6470Source
	{
		$this->target = $target;

		return $this;
	}

}
