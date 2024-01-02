<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\OneToOne;
use Doctrine\ORM\Mapping\Table;
use Doctrine\Tests\OrmFunctionalTestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests basic operations on entities with default values.
 */
class DefaultValuesTest extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchemaForModels(
            DefaultValueUser::class,
            DefaultValueAddress::class,
        );
    }

    #[Group('non-cacheable')]
    public function testSimpleDetachMerge(): void
    {
        $user       = new DefaultValueUser();
        $user->name = 'romanb';
        $this->_em->persist($user);
        $this->_em->flush();
        $this->_em->clear();

        $userId = $user->id; // e.g. from $_REQUEST
        $user2  = $this->_em->getReference($user::class, $userId);

        $this->_em->flush();
        self::assertTrue($this->isUninitializedObject($user2));

        $a          = new DefaultValueAddress();
        $a->country = 'de';
        $a->zip     = '12345';
        $a->city    = 'Berlin';
        $a->street  = 'Sesamestreet';

        $a->user = $user2;
        $this->_em->persist($a);
        $this->_em->flush();

        self::assertTrue($this->isUninitializedObject($user2));
        $this->_em->clear();

        $a2 = $this->_em->find($a::class, $a->id);
        self::assertInstanceOf(DefaultValueUser::class, $a2->getUser());
        self::assertEquals($userId, $a2->getUser()->getId());
        self::assertEquals('Poweruser', $a2->getUser()->type);
    }
}


#[Table(name: 'defaultvalueuser')]
#[Entity]
class DefaultValueUser
{
    /** @var int */
    #[Id]
    #[Column(type: 'integer')]
    #[GeneratedValue(strategy: 'AUTO')]
    public $id;
    /** @var string */
    #[Column(type: 'string', length: 255)]
    public $name = '';
    /** @var string */
    #[Column(type: 'string', length: 255)]
    public $type = 'Poweruser';
    /** @var DefaultValueAddress */
    #[OneToOne(targetEntity: 'DefaultValueAddress', mappedBy: 'user', cascade: ['persist'])]
    public $address;

    public function getId(): int
    {
        return $this->id;
    }
}

/**
 * CmsAddress
 */
#[Table(name: 'defaultvalueaddresses')]
#[Entity]
class DefaultValueAddress
{
    /** @var int */
    #[Column(type: 'integer')]
    #[Id]
    #[GeneratedValue(strategy: 'AUTO')]
    public $id;

    /** @var string */
    #[Column(type: 'string', length: 50)]
    public $country;

    /** @var string */
    #[Column(type: 'string', length: 50)]
    public $zip;

    /** @var string */
    #[Column(type: 'string', length: 50)]
    public $city;

    /**
     * @var string
     * Testfield for Schema Updating Tests.
     */
    public $street;

    /** @var DefaultValueUser */
    #[OneToOne(targetEntity: 'DefaultValueUser')]
    #[JoinColumn(name: 'user_id', referencedColumnName: 'id')]
    public $user;

    public function getUser(): DefaultValueUser
    {
        return $this->user;
    }
}
