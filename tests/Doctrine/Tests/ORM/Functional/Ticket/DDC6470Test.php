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
				DDC6470Target1::class,
				DDC6470Target2::class,
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
		$this->assertTrue($this->_em->getCache()->containsEntity(DDC6470Target1::class, ['id' => $target2->getId()]));


		$queryCount = $this->getCurrentQueryCount();

		/** @var EntityRepository $er */
		$er = $this->_em->getRepository(DDC6470Source::class);
		$qb = $er->createQueryBuilder("n");
		$qb->setCacheable(true)->setLifetime(3 * 60)->setCacheRegion($region = time());

		$qb->getQuery()->getResult();

		$newQueryCount = $this->getCurrentQueryCount();
		$this->assertEquals($queryCount + 1, $newQueryCount, "One for query only. One more appears here @see UnitOfWork:2654.");

		$this->_em->clear();

		/** @var EntityRepository $er */
		$er = $this->_em->getRepository(DDC6470Source::class);
		$qb = $er->createQueryBuilder("n");
		$qb->setCacheable(true)->setLifetime(3 * 60)->setCacheRegion($region);
		$qb->getQuery()->getResult();
		$this->assertEquals($newQueryCount, $this->getCurrentQueryCount(), "Assert everything get from cache.");
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
