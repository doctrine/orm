<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\Deprecations\PHPUnit\VerifyDeprecations;
use Doctrine\ORM\Internal\SQLResultCasing;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Query\ResultSetMappingBuilder;
use Doctrine\Tests\OrmFunctionalTestCase;

/**
 * @group GH-9600
 */
class GH9600Test extends OrmFunctionalTestCase
{
    use SQLResultCasing;
    use VerifyDeprecations;

    /** @var AbstractPlatform */
    private $platform = null;

    /** @var array<string, list<class-string>> */
    protected static $modelSets = [
        'native_query_test' => [
            GH9600User::class,
            GH9600Address::class,
        ],
    ];

    protected function setUp(): void
    {
        $this->useModelSet('native_query_test');
        parent::setUp();

        $this->platform = $this->_em->getConnection()->getDatabasePlatform();
    }

    public function testBasicNativeQuery(): void
    {
        $this->addRowToDb('Homer', 'Springfield', 'Evergreen Terrace');
        $this->addRowToDb('Sherlock', 'London', 'Baker Street');

        $rsm = new ResultSetMappingBuilder($this->_em);
        $rsm->addRootEntityFromClassMetadata(GH9600User::class, 'u');
        $rsm->addJoinedEntityFromClassMetadata(GH9600Address::class, 'a', 'u', 'address');

        $query = '
            SELECT
                u.*,
                a.*
            FROM users u
            LEFT JOIN addresses a ON (u.address_id = a.a_id)
        ';

        $native = $this->_em->createNativeQuery($query, $rsm);
        $result1 = $native->getResult();

        $this->_em->clear();

        $query = '
            SELECT
                a.*,
                u.*
            FROM users u
            LEFT JOIN addresses a ON (u.address_id = a.a_id)
        ';

        $native = $this->_em->createNativeQuery($query, $rsm);

        $result2 = $native->getResult();

        self::assertEquals($result1, $result2);
    }

    private function addRowToDb(string $name, string $city, string $street): void
    {
        $address = new GH9600Address();
        $address->city = $city;
        $address->street = $street;
        $this->_em->persist($address);
        $this->_em->flush();

        $user = new GH9600User();
        $user->name = $name;
        $user->address = $address;
        $this->_em->persist($user);
        $this->_em->flush();

        $this->_em->clear();
    }
}

/**
 * @ORM\Table(name="addresses")
 * @ORM\Entity
 */
class GH9600Address
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer", name="a_id")
     * @ORM\GeneratedValue
     */
    public $id;

    /**
     * @ORM\Column(type="string", name="a_street")
     */
    public $street;

    /**
     * @ORM\Column(type="string", name="a_city")
     */
    public $city;

    /**
     * @ORM\OneToOne(targetEntity="GH9600User", mappedBy="address")
     */
    public $user;
}

/**
 * @ORM\Table(name="users")
 * @ORM\Entity
 */
class GH9600User
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer",name="u_id")
     * @ORM\GeneratedValue
     */
    public $id;

    /**
     * @ORM\Column(type="string", name="u_name")
     */
    public $name;

    /**
     * @ORM\OneToOne(targetEntity="GH9600Address", inversedBy="user")
     * @ORM\JoinColumn(referencedColumnName="a_id")
     */
    public $address;
}
