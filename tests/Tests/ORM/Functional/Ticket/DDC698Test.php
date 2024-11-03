<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\InverseJoinColumn;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\JoinTable;
use Doctrine\ORM\Mapping\ManyToMany;
use Doctrine\ORM\Mapping\Table;
use Doctrine\Tests\OrmFunctionalTestCase;

use function strtolower;

class DDC698Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchemaForModels(DDC698Role::class, DDC698Privilege::class);
    }

    public function testTicket(): void
    {
        $qb = $this->_em->createQueryBuilder();
        $qb->select('p', 'r')
           ->from(__NAMESPACE__ . '\DDC698Privilege', 'p')
           ->leftJoin('p.roles', 'r');

        $sql = $qb->getQuery()->getSQL();

        self::assertEquals(
            strtolower('SELECT p0_.privilegeID AS privilegeID_0, p0_.name AS name_1, r1_.roleID AS roleID_2, r1_.name AS name_3, r1_.shortName AS shortName_4 FROM Privileges p0_ LEFT JOIN RolePrivileges r2_ ON p0_.privilegeID = r2_.privilegeID LEFT JOIN Roles r1_ ON r1_.roleID = r2_.roleID'),
            strtolower($sql),
        );
    }
}

#[Table(name: 'Roles')]
#[Entity]
class DDC698Role
{
    /** @var int */
    #[Id]
    #[Column(name: 'roleID', type: 'integer')]
    #[GeneratedValue(strategy: 'AUTO')]
    protected $roleID;

    /** @var string */
    #[Column(name: 'name', type: 'string', length: 45)]
    protected $name;

    /** @var string */
    #[Column(name: 'shortName', type: 'string', length: 45)]
    protected $shortName;

    /** @var Collection<int, DDC698Privilege> */
    #[JoinTable(name: 'RolePrivileges')]
    #[JoinColumn(name: 'roleID', referencedColumnName: 'roleID')]
    #[InverseJoinColumn(name: 'privilegeID', referencedColumnName: 'privilegeID')]
    #[ManyToMany(targetEntity: 'DDC698Privilege', inversedBy: 'roles')]
    protected $privilege;
}


#[Table(name: 'Privileges')]
#[Entity]
class DDC698Privilege
{
    /** @var int */
    #[Id]
    #[Column(name: 'privilegeID', type: 'integer')]
    #[GeneratedValue(strategy: 'AUTO')]
    protected $privilegeID;

    /** @var string */
    #[Column(name: 'name', type: 'string', length: 45)]
    protected $name;

    /** @phpstan-var Collection<int, DDC698Role> */
    #[ManyToMany(targetEntity: 'DDC698Role', mappedBy: 'privilege')]
    protected $roles;
}
