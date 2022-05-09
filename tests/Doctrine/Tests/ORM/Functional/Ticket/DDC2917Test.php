<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\DiscriminatorColumn;
use Doctrine\ORM\Mapping\DiscriminatorMap;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\InheritanceType;
use Doctrine\Tests\OrmFunctionalTestCase;

use function strtolower;

class DDC2917Test extends OrmFunctionalTestCase
{
    /**
     * @dataProvider provideDqlToSqlCases
     */
    public function testCorrectGroupByStatement(string $dql, string $sql): void
    {
        $q = $this->_em->createQuery($dql);

        self::assertEquals(
            strtolower($sql),
            strtolower($q->getSQL())
        );
    }

    public function provideDqlToSqlCases(): array
    {
        return [
            [
                'select u from ' . __NAMESPACE__ . '\\DDC2917User u GROUP BY u.id',
                'SELECT d0_.id AS id_0, d1_.name AS name_1, d2_.foo AS foo_2, d3_.name AS name_3, d0_.type AS type_4 FROM DDC2917User d0_ LEFT JOIN DDC2917Admin d1_ ON d0_.id = d1_.id LEFT JOIN DDC2917SuperAdmin d2_ ON d0_.id = d2_.id LEFT JOIN DDC2917Client d3_ ON d0_.id = d3_.id GROUP BY d0_.id, d1_.id, d2_.id, d3_.id',
            ],
            [
                'select u from ' . __NAMESPACE__ . '\\DDC2917User u GROUP BY u',
                'SELECT d0_.id AS id_0, d1_.name AS name_1, d2_.foo AS foo_2, d3_.name AS name_3, d0_.type AS type_4 FROM DDC2917User d0_ LEFT JOIN DDC2917Admin d1_ ON d0_.id = d1_.id LEFT JOIN DDC2917SuperAdmin d2_ ON d0_.id = d2_.id LEFT JOIN DDC2917Client d3_ ON d0_.id = d3_.id GROUP BY d0_.id, d1_.id, d2_.id, d3_.id',
            ],
            [
                'select a from ' . __NAMESPACE__ . '\\DDC2917Admin a GROUP BY a.id',
                'SELECT d0_.id AS id_0, d1_.name AS name_1, d2_.foo AS foo_2, d0_.type AS type_3 FROM DDC2917Admin d1_ INNER JOIN DDC2917User d0_ ON d1_.id = d0_.id LEFT JOIN DDC2917SuperAdmin d2_ ON d1_.id = d2_.id GROUP BY d0_.id, d1_.id, d2_.id',
            ],
            [
                'select a from ' . __NAMESPACE__ . '\\DDC2917SuperAdmin a GROUP BY a.id',
                'SELECT d0_.id AS id_0, d1_.name AS name_1, d2_.foo AS foo_2, d0_.type AS type_3 FROM DDC2917SuperAdmin d2_ INNER JOIN DDC2917Admin d1_ ON d2_.id = d1_.id INNER JOIN DDC2917User d0_ ON d2_.id = d0_.id GROUP BY d0_.id, d1_.id, d2_.id',
            ],
        ];
    }
}

/**
 * @Entity
 * @InheritanceType("JOINED")
 * @DiscriminatorColumn(name="type", type="string")
 * @DiscriminatorMap({
 *     "admin" = "DDC2917Admin",
 *     "superadmin" = DDC2917SuperAdmin::class,
 *     "client" = DDC2917Client::class,
 * })
 */
class DDC2917User
{
    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    public $id;
}

/**
 * @Entity
 */
class DDC2917Admin extends DDC2917User
{
    /* @Column(type="string", length=255) */
    public $name;
}

/**
 * @Entity
 */
class DDC2917SuperAdmin extends DDC2917Admin
{
    /** @Column(type="boolean") */
    public $foo;
}

/**
 * @Entity
 */
class DDC2917Client extends DDC2917User
{
    /** @Column(type="string", length=255) */
    public $name;
}
