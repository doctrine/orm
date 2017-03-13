<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Annotation as ORM;

class DDC698Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    protected function setUp()
    {
        parent::setUp();
        try {
            $this->schemaTool->createSchema(
                [
                $this->em->getClassMetadata(DDC698Role::class),
                $this->em->getClassMetadata(DDC698Privilege::class)
                ]
            );
        } catch(\Exception $e) {

        }
    }

    public function testTicket()
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select('p', 'r')
		   ->from(__NAMESPACE__ .  '\DDC698Privilege', 'p')
		   ->leftJoin('p.roles', 'r');

        self::assertSQLEquals(
            'SELECT t0."privilegeID" AS c0, t0."name" AS c1, t1."roleID" AS c2, t1."name" AS c3, t1."shortName" AS c4 FROM "Privileges" t0 LEFT JOIN "RolePrivileges" t2 ON t0."privilegeID" = t2."privilegeID" LEFT JOIN "Roles" t1 ON t1."roleID" = t2."roleID"',
            $qb->getQuery()->getSQL()
        );
    }
}

/**
 *
 * @ORM\Table(name="Roles")
 * @ORM\Entity
 */
class DDC698Role
{
	/**
	 *  @ORM\Id @ORM\Column(name="roleID", type="integer")
	 *  @ORM\GeneratedValue(strategy="AUTO")
	 *
	 */
	protected $roleID;

	/**
	 * @ORM\Column(name="name", type="string", length=45)
	 *
	 *
	 */
	protected $name;

	/**
	 * @ORM\Column(name="shortName", type="string", length=45)
	 *
	 *
	 */
	protected $shortName;



	/**
	 * @ORM\ManyToMany(targetEntity="DDC698Privilege", inversedBy="roles")
	 * @ORM\JoinTable(name="RolePrivileges",
	 *     joinColumns={@ORM\JoinColumn(name="roleID", referencedColumnName="roleID")},
	 *     inverseJoinColumns={@ORM\JoinColumn(name="privilegeID", referencedColumnName="privilegeID")}
	 * )
	 */
	protected $privilege;

}


/**
 *
 * @ORM\Table(name="Privileges")
 * @ORM\Entity()
 */
class DDC698Privilege
{
	/**
	 *  @ORM\Id  @ORM\Column(name="privilegeID", type="integer")
	 *  @ORM\GeneratedValue(strategy="AUTO")
	 *
	 */
	protected $privilegeID;

	/**
	 * @ORM\Column(name="name", type="string", length=45)
	 *
	 *
	 */
	protected $name;

	/**
     * @ORM\ManyToMany(targetEntity="DDC698Role", mappedBy="privilege")
     */
	protected $roles;
}
