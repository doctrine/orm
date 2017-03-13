<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Annotation as ORM;

class DDC719Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    protected function setUp()
    {
        parent::setUp();
        //$this->em->getConnection()->getConfiguration()->setSQLLogger(new \Doctrine\DBAL\Logging\EchoSQLLogger);
        $this->schemaTool->createSchema(
            [
            $this->em->getClassMetadata(DDC719Group::class),
            ]
        );
    }

    public function testIsEmptySqlGeneration()
    {
        $q = $this->em->createQuery(
            'SELECT g, c FROM Doctrine\Tests\ORM\Functional\Ticket\DDC719Group g LEFT JOIN g.children c  WHERE g.parents IS EMPTY'
        );

        self::assertSQLEquals(
            'SELECT t0."id" AS c0, t0."name" AS c1, t0."description" AS c2, t1."id" AS c3, t1."name" AS c4, t1."description" AS c5 FROM "groups" t0 LEFT JOIN "groups_groups" t2 ON t0."id" = t2."parent_id" LEFT JOIN "groups" t1 ON t1."id" = t2."child_id" WHERE (SELECT COUNT(*) FROM "groups_groups" t3 WHERE t3."child_id" = t0."id") = 0',
            $q->getSQL()
        );
    }
}

/**
 * @ORM\MappedSuperclass
 */
class Entity
{
    /**
     * @ORM\Id @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    protected $id;

    public function getId() { return $this->id; }
}

/**
 * @ORM\Entity
 * @ORM\Table(name="groups")
 */
class DDC719Group extends Entity {
    /** @ORM\Column(type="string", nullable=false) */
    protected $name;

	/** @ORM\Column(type="string", nullable=true) */
	protected $description;

	/**
	 * @ORM\ManyToMany(targetEntity="DDC719Group", inversedBy="parents")
	 * @ORM\JoinTable(name="groups_groups",
	 * 		joinColumns={@ORM\JoinColumn(name="parent_id", referencedColumnName="id")},
	 * 		inverseJoinColumns={@ORM\JoinColumn(name="child_id", referencedColumnName="id")}
	 * )
	 */
	protected $children = NULL;

	/**
	 * @ORM\ManyToMany(targetEntity="DDC719Group", mappedBy="children")
	 */
	protected $parents = NULL;

	/**
	 * construct
	 */
	public function __construct() {
		parent::__construct();

		$this->channels = new ArrayCollection();
		$this->children = new ArrayCollection();
		$this->parents = new ArrayCollection();
	}

	/**
	 * adds group as new child
	 *
	 * @param Group $child
	 */
	public function addGroup(Group $child) {
        if ( ! $this->children->contains($child)) {
            $this->children->add($child);
            $child->addGroup($this);
        }
	}

	/**
	 * adds channel as new child
	 *
	 * @param Channel $child
	 */
	public function addChannel(Channel $child) {
        if ( ! $this->channels->contains($child)) {
            $this->channels->add($child);
        }
	}

	/**
	 * getter & setter
	 */
	public function getName() { return $this->name; }
	public function setName($name) { $this->name = $name; }
	public function getDescription() { return $this->description; }
	public function setDescription($description) { $this->description = $description; }
	public function getChildren() { return $this->children; }
	public function getParents() { return $this->parents; }
	public function getChannels() { return $this->channels; }
}
