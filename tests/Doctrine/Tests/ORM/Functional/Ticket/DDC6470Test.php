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

		$this->setUpEntitySchema([
			DDC6470Source::class,
			DDC6470Target1::class,
			DDC6470Target2::class,
			DDC6470SourceReverse::class,
			DDC6470Target2Reverse::class,
			DDC6470Target1Reverse::class,
		]);
	}

	/**
	 * @throws \Doctrine\Common\Persistence\Mapping\MappingException
	 * @throws \Doctrine\ORM\ORMException
	 * @throws \Doctrine\ORM\OptimisticLockException
	 */
	public function testOneEntity()
	{
		$source = new DDC6470Source();
		$target = new DDC6470Target1();
		$source->setTarget1($target);

		$this->_em->persist($source);
		$this->_em->flush();
		$this->_em->clear();

		$this->assertTrue($this->_em->getCache()->containsEntity(DDC6470Source::class, ['id' => $source->getId()]));
		$this->assertTrue($this->_em->getCache()->containsEntity(DDC6470Target1::class, ['id' => $target->getId()]));

		$queryCount = $this->getCurrentQueryCount();

		/** @var EntityRepository $er */
		$er = $this->_em->getRepository(DDC6470Source::class);
		$qb = $er->createQueryBuilder("n");
		$qb->setCacheable(true)->setLifetime(3 * 60)->setCacheRegion($region = time());

		$qb->getQuery()->getResult();

		$newQueryCount = $this->getCurrentQueryCount();
		$this->assertEquals($queryCount + 1, $newQueryCount, "+1 for query only. One more appears here @see UnitOfWork:2654. Acutally, it's not valid behaviour.");

		$this->_em->clear();

		/** @var EntityRepository $er */
		$er = $this->_em->getRepository(DDC6470Source::class);
		$qb = $er->createQueryBuilder("n");
		$qb->setCacheable(true)->setLifetime(3 * 60)->setCacheRegion($region);
		$qb->getQuery()->getResult();
		$this->assertEquals($newQueryCount, $this->getCurrentQueryCount(), "Assert everything get from cache. Because of prev assertion, check for sure");
	}

	/**
	 * @throws \Doctrine\Common\Persistence\Mapping\MappingException
	 * @throws \Doctrine\ORM\ORMException
	 * @throws \Doctrine\ORM\OptimisticLockException
	 */
	public function testMultipleEntities()
	{

		$source1 = new DDC6470Source();
		$target1 = new DDC6470Target1();
		$source1->setTarget1($target1);

		$source2 = new DDC6470Source();
		$target2 = new DDC6470Target2();
		$source2->setTarget2($target2);

		$this->_em->persist($source1);
		$this->_em->persist($source2);
		$this->_em->flush();
		$this->_em->clear();

		$this->assertTrue($this->_em->getCache()->containsEntity(DDC6470Source::class, ['id' => $source1->getId()]));
		$this->assertTrue($this->_em->getCache()->containsEntity(DDC6470Source::class, ['id' => $source2->getId()]));
		$this->assertTrue($this->_em->getCache()->containsEntity(DDC6470Target1::class, ['id' => $target1->getId()]));
		$this->assertTrue($this->_em->getCache()->containsEntity(DDC6470Target2::class, ['id' => $target2->getId()]));

		$queryCount = $this->getCurrentQueryCount();

		/** @var EntityRepository $er */
		$er = $this->_em->getRepository(DDC6470Source::class);
		$qb = $er->createQueryBuilder("n");
		$qb->setCacheable(true)->setLifetime(3 * 60)->setCacheRegion($region = time());

		$qb->getQuery()->getResult();

		$newQueryCount = $this->getCurrentQueryCount();
		$this->assertEquals($queryCount + 1, $newQueryCount, "+1 for query only. One more appears here @see UnitOfWork:2654.");

		$this->_em->clear();

		/** @var EntityRepository $er */
		$er = $this->_em->getRepository(DDC6470Source::class);
		$qb = $er->createQueryBuilder("n");
		$qb->setCacheable(true)->setLifetime(3 * 60)->setCacheRegion($region);
		$qb->getQuery()->getResult();
		$this->assertEquals($newQueryCount, $this->getCurrentQueryCount(), "Assert everything get from cache. 
		This is the main problem. 
		The data hydrated in DefaultQueryCache::get() is not put into cache properly, some oneToOne relations are missed");
	}

	/**
	 * @throws \Doctrine\Common\Persistence\Mapping\MappingException
	 * @throws \Doctrine\ORM\ORMException
	 * @throws \Doctrine\ORM\OptimisticLockException
	 */
	public function testMultipleEntitiesReverse()
	{

		$target1 = new DDC6470Target1Reverse();
		$source1 = new DDC6470SourceReverse();
		$target1->setSource($source1);

		$target2 = new DDC6470Target2Reverse();
		$source2 = new DDC6470SourceReverse();
		$target2->setSource($source2);

		$this->_em->persist($target1);
		$this->_em->persist($target2);
		$this->_em->flush();
		$this->_em->clear();

		$this->assertTrue($this->_em->getCache()->containsEntity(DDC6470SourceReverse::class, ['id' => $source1->getId()]));
		$this->assertTrue($this->_em->getCache()->containsEntity(DDC6470SourceReverse::class, ['id' => $source2->getId()]));
		$this->assertTrue($this->_em->getCache()->containsEntity(DDC6470Target1Reverse::class, ['id' => $target1->getId()]));
		$this->assertTrue($this->_em->getCache()->containsEntity(DDC6470Target2Reverse::class, ['id' => $target2->getId()]));

		$queryCount = $this->getCurrentQueryCount();

		/** @var EntityRepository $er */
		$er = $this->_em->getRepository(DDC6470Target1Reverse::class);
		$qb = $er->createQueryBuilder("n");
		$qb->setCacheable(true)->setLifetime(3 * 60)->setCacheRegion($region1 = time());
		$qb->getQuery()->getResult();

		$newQueryCount = $this->getCurrentQueryCount();
		$this->assertEquals($queryCount + 1, $newQueryCount, "+1 for query only. One more appears here @see UnitOfWork:2654.");
		$this->_em->clear();

		$queryCount = $newQueryCount;
		/** @var EntityRepository $er */
		$er = $this->_em->getRepository(DDC6470Target2Reverse::class);
		$qb = $er->createQueryBuilder("n");
		$qb->setCacheable(true)->setLifetime(3 * 60)->setCacheRegion($region2 = time());
		$qb->getQuery()->getResult();

		$newQueryCount = $this->getCurrentQueryCount();
		$this->assertEquals($queryCount + 1, $newQueryCount, "+1 for query only. One more appears here @see UnitOfWork:2654.");
		$this->_em->clear();

		$queryCount = $newQueryCount;
		/** @var EntityRepository $er */
		$er = $this->_em->getRepository(DDC6470Target1Reverse::class);
		$qb = $er->createQueryBuilder("n");
		$qb->setCacheable(true)->setLifetime(3 * 60)->setCacheRegion($region1);
		$qb->getQuery()->getResult();

		$newQueryCount = $this->getCurrentQueryCount();
		$this->assertEquals($queryCount, $newQueryCount, "Assert everything get from cache.");
		$this->_em->clear();

		$queryCount = $newQueryCount;
		/** @var EntityRepository $er */
		$er = $this->_em->getRepository(DDC6470Target2Reverse::class);
		$qb = $er->createQueryBuilder("n");
		$qb->setCacheable(true)->setLifetime(3 * 60)->setCacheRegion($region2);
		$qb->getQuery()->getResult();

		$newQueryCount = $this->getCurrentQueryCount();
		$this->assertEquals($queryCount, $newQueryCount, "Assert everything get from cache.");
		$this->_em->clear();
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
	 * @OneToOne(targetEntity="Doctrine\Tests\ORM\Functional\Ticket\DDC6470Target1", mappedBy="source", cascade={"persist"})
	 * @var DDC6470Target1
	 */
	protected $target1;
	/**
	 * @Cache("NONSTRICT_READ_WRITE")
	 * @OneToOne(targetEntity="Doctrine\Tests\ORM\Functional\Ticket\DDC6470Target2", mappedBy="source", cascade={"persist"})
	 * @var DDC6470Target2
	 */
	protected $target2;

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
	 * @return DDC6470Target1
	 */
	public function getTarget1(): ?DDC6470Target1
	{
		return $this->target1;
	}

	/**
	 * @param DDC6470Target1 $target
	 * @return DDC6470Source
	 */
	public function setTarget1(?DDC6470Target1 $target): DDC6470Source
	{
		$this->target1 = $target;

		return $this;
	}

	/**
	 * @return DDC6470Target2
	 */
	public function getTarget2(): ?DDC6470Target2
	{
		return $this->target2;
	}

	/**
	 * @param DDC6470Target2 $target2
	 * @return DDC6470Source
	 */
	public function setTarget2(?DDC6470Target2 $target2): DDC6470Source
	{
		$this->target2 = $target2;

		return $this;
	}

}

/**
 * @Entity()
 * @Cache(usage="NONSTRICT_READ_WRITE")
 */
class DDC6470Target1
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
	 * @OneToOne(targetEntity="Doctrine\Tests\ORM\Functional\Ticket\DDC6470Source", inversedBy="target1")
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
	 * @return DDC6470Target1
	 */
	public function setId(?int $id): DDC6470Target1
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
	 * @return DDC6470Target1
	 */
	public function setSource(?DDC6470Source $source): DDC6470Target1
	{
		$this->source = $source;

		return $this;
	}

}

/**
 * @Entity()
 * @Cache(usage="NONSTRICT_READ_WRITE")
 */
class DDC6470Target2
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
	 * @OneToOne(targetEntity="Doctrine\Tests\ORM\Functional\Ticket\DDC6470Source", inversedBy="target2")
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
	 * @return DDC6470Target2
	 */
	public function setId(?int $id): DDC6470Target2
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
	 * @return DDC6470Target2
	 */
	public function setSource(?DDC6470Source $source): DDC6470Target2
	{
		$this->source = $source;

		return $this;
	}

}

/**
 * @Entity
 * @Cache(usage="NONSTRICT_READ_WRITE")
 */
class DDC6470SourceReverse
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
	 * @OneToOne(targetEntity="Doctrine\Tests\ORM\Functional\Ticket\DDC6470Target1Reverse", inversedBy="source")
	 * @var DDC6470Target1Reverse
	 */
	protected $target1;
	/**
	 * @Cache("NONSTRICT_READ_WRITE")
	 * @OneToOne(targetEntity="Doctrine\Tests\ORM\Functional\Ticket\DDC6470Target2Reverse", inversedBy="source")
	 * @var DDC6470Target2Reverse
	 */
	protected $target2;

	/**
	 * @return int
	 */
	public function getId(): ?int
	{
		return $this->id;
	}

	/**
	 * @param int $id
	 * @return DDC6470SourceReverse
	 */
	public function setId(?int $id): DDC6470SourceReverse
	{
		$this->id = $id;

		return $this;
	}

	/**
	 * @return DDC6470Target1Reverse
	 */
	public function getTarget1(): ?DDC6470Target1Reverse
	{
		return $this->target1;
	}

	/**
	 * @param DDC6470Target1Reverse $target1
	 * @return DDC6470SourceReverse
	 */
	public function setTarget1(?DDC6470Target1Reverse $target1): DDC6470SourceReverse
	{
		$this->target1 = $target1;

		return $this;
	}

	/**
	 * @return DDC6470Target2Reverse
	 */
	public function getTarget2(): ?DDC6470Target2Reverse
	{
		return $this->target2;
	}

	/**
	 * @param DDC6470Target2Reverse $target2
	 * @return DDC6470SourceReverse
	 */
	public function setTarget2(?DDC6470Target2Reverse $target2): DDC6470SourceReverse
	{
		$this->target2 = $target2;

		return $this;
	}

}

/**
 * @Entity()
 * @Cache(usage="NONSTRICT_READ_WRITE")
 */
class DDC6470Target1Reverse
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
	 * @OneToOne(targetEntity="Doctrine\Tests\ORM\Functional\Ticket\DDC6470SourceReverse", mappedBy="target1", cascade={"persist"})
	 * @var DDC6470SourceReverse
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
	 * @return DDC6470Target1Reverse
	 */
	public function setId(?int $id): DDC6470Target1Reverse
	{
		$this->id = $id;

		return $this;
	}

	/**
	 * @return DDC6470SourceReverse
	 */
	public function getSource(): ?DDC6470SourceReverse
	{
		return $this->source;
	}

	/**
	 * @param DDC6470SourceReverse $source
	 * @return DDC6470Target1Reverse
	 */
	public function setSource(?DDC6470SourceReverse $source): DDC6470Target1Reverse
	{
		$this->source = $source;

		return $this;
	}

}

/**
 * @Entity()
 * @Cache(usage="NONSTRICT_READ_WRITE")
 */
class DDC6470Target2Reverse
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
	 * @OneToOne(targetEntity="Doctrine\Tests\ORM\Functional\Ticket\DDC6470SourceReverse", mappedBy="target2", cascade={"persist"})
	 * @var DDC6470SourceReverse
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
	 * @return DDC6470Target2Reverse
	 */
	public function setId(?int $id): DDC6470Target2Reverse
	{
		$this->id = $id;

		return $this;
	}

	/**
	 * @return DDC6470SourceReverse
	 */
	public function getSource(): ?DDC6470SourceReverse
	{
		return $this->source;
	}

	/**
	 * @param DDC6470SourceReverse $source
	 * @return DDC6470Target2Reverse
	 */
	public function setSource(?DDC6470SourceReverse $source): DDC6470Target2Reverse
	{
		$this->source = $source;

		return $this;
	}
}
