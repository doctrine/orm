<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

require_once __DIR__ . '/../../../TestInit.php';

class DDC719Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    protected function setUp()
    {
        parent::setUp();
        //$this->_em->getConnection()->getConfiguration()->setSQLLogger(new \Doctrine\DBAL\Logging\EchoSQLLogger);
        $this->_schemaTool->createSchema(array(
            $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC719Group'),
        ));
    }

    public function testIsEmptySqlGeneration()
    {
        $q = $this->_em->createQuery('SELECT g, c FROM Doctrine\Tests\ORM\Functional\Ticket\DDC719Group g LEFT JOIN g.children c  WHERE g.parents IS EMPTY');

        $this->assertEquals(
            strtolower('SELECT g0_.name AS name0, g0_.description AS description1, g0_.id AS id2, g1_.name AS name3, g1_.description AS description4, g1_.id AS id5 FROM groups g0_ LEFT JOIN groups_groups g2_ ON g0_.id = g2_.parent_id LEFT JOIN groups g1_ ON g1_.id = g2_.child_id WHERE (SELECT COUNT(*) FROM groups_groups g3_ WHERE g3_.child_id = g0_.id) = 0'),
            strtolower($q->getSQL())
        );
    }
}

/**
 * @MappedSuperclass
 */
class Entity
{
    /**
     * @Id @GeneratedValue
     * @Column(type="integer")
     */
    protected $id;

    public function getId() { return $this->id; }
}

/**
 * @Entity
 * @Table(name="groups")
 */
class DDC719Group extends Entity {
    /** @Column(type="string", nullable=false) */
    protected $name;

	/** @Column(type="string", nullable=true) */
	protected $description;

	/**
	 * @ManyToMany(targetEntity="DDC719Group", inversedBy="parents")
	 * @JoinTable(name="groups_groups",
	 * 		joinColumns={@JoinColumn(name="parent_id", referencedColumnName="id")},
	 * 		inverseJoinColumns={@JoinColumn(name="child_id", referencedColumnName="id")}
	 * )
	 */
	protected $children = NULL;

	/**
	 * @ManyToMany(targetEntity="DDC719Group", mappedBy="children")
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