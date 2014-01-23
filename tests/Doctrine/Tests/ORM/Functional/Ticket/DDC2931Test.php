<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;

require_once __DIR__ . '/../../../TestInit.php';

/**
 * @group DDC-2931
 */
class DDC2931Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    public function setUp()
    {
        parent::setUp();
        try {
            $this->_schemaTool->createSchema(array(
                $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC2931User'),
            ));
        } catch(\Exception $e) {

        }
    }

    public function testIssue()
    {
 		$first = new DDC2931User(null);
		$this->_em->persist($first);

		$second = new DDC2931User($first);
		$this->_em->persist($second);

		$third = new DDC2931User($second);
		$this->_em->persist($third);
		
		
		$this->_em->flush();
		$this->_em->clear();

		// Load Entity in second order
		$second = $this->_em->find('Doctrine\Tests\ORM\Functional\Ticket\DDC2931User', $second->getId());
		// crash here with "Segmentation error" caused by infinite loop. This work correctly with doctrine 2.3 
		$second->getRank();
    }
}


/**
 * @Entity
 */
class DDC2931User
{

	/**
	 * @Id
	 * @Column(type="integer")
	 * @GeneratedValue(strategy="AUTO")
	 */
	protected $id;

	/**
	 *
	 * @OneToOne(targetEntity="DDC2931User", inversedBy="child")
	 * @JoinColumn(name="parent_id", referencedColumnName="id", nullable = true)
	 **/
	protected $parent;

	/**
	 * @OneToOne(targetEntity="DDC2931User", mappedBy="parent")
	 **/
	protected $child;


	/**
	 * Constructeur.
	 */
	public function __construct ($parent = null)
	{
		$this->parent = $parent;
	}

	/**
	 * Return Rank recursively
	 * My rank is 1 + rank of my parent
	 * @return integer
	 */
	public function getRank()
	{
		if($this->parent == null)
			return 1;
		return 1 + $this->parent->getRank();
	}

	/**
	 * @return the $id
	 */
	public function getId ()
	{
		return $this->id;
	}

	/**
	 * @return the $parent
	 */
	public function getParent ()
	{
		return $this->parent;
	}


	/**
	 * @param integer $id
	 */
	public function setId ($id)
	{
		$this->id = $id;
	}

	/**
	 * @param DDC2931User $parent
	 */
	public function setParent ($parent)
	{
		$this->parent = $parent;
	}


	/**
	 * @return the $child
	 */
	public function getChild ()
	{
		return $this->child;
	}

	/**
	 * @param DDC2931User $child
	 */
	public function setChild ($child)
	{
		$this->child = $child;
	}

	/**
	 * Magic getter to expose protected properties.
	 *
	 * @param string $property
	 * @return mixed
	 */
	public function __get ($property)
	{
		return $this->$property;
	}

	/**
	 * Magic setter to save protected properties.
	 *
	 * @param string $property
	 * @param mixed $value
	 */
	public function __set ($property, $value)
	{
		$this->$property = $value;
	}

}
