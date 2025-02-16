<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\OneToOne;
use Doctrine\Tests\OrmFunctionalTestCase;
use PHPUnit\Framework\Attributes\Group;

#[Group('DDC-1228')]
#[Group('DDC-1226')]
class DDC1228Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchemaForModels(DDC1228User::class, DDC1228Profile::class);
    }

    public function testOneToOnePersist(): void
    {
        $user          = new DDC1228User();
        $profile       = new DDC1228Profile();
        $profile->name = 'Foo';
        $user->profile = $profile;

        $this->_em->persist($user);
        $this->_em->persist($profile);
        $this->_em->flush();
        $this->_em->clear();

        $user = $this->_em->find(DDC1228User::class, $user->id);

        self::assertTrue($this->isUninitializedObject($user->getProfile()), 'Proxy is not initialized');
        $user->getProfile()->setName('Bar');
        self::assertFalse($this->isUninitializedObject($user->getProfile()), 'Proxy is not initialized');

        self::assertEquals('Bar', $user->getProfile()->getName());
        self::assertEquals(['id' => 1, 'name' => 'Foo'], $this->_em->getUnitOfWork()->getOriginalEntityData($user->getProfile()));

        $this->_em->flush();
        $this->_em->clear();

        $user = $this->_em->find(DDC1228User::class, $user->id);
        self::assertEquals('Bar', $user->getProfile()->getName());
    }

    public function testRefresh(): void
    {
        $user          = new DDC1228User();
        $profile       = new DDC1228Profile();
        $profile->name = 'Foo';
        $user->profile = $profile;

        $this->_em->persist($user);
        $this->_em->persist($profile);
        $this->_em->flush();
        $this->_em->clear();

        $user = $this->_em->getReference(DDC1228User::class, $user->id);

        $this->_em->refresh($user);
        $user->name = 'Baz';
        $this->_em->flush();
        $this->_em->clear();

        $user = $this->_em->find(DDC1228User::class, $user->id);
        self::assertEquals('Baz', $user->name);
    }
}

#[Entity]
class DDC1228User
{
    /** @var int */
    #[Id]
    #[Column(type: 'integer')]
    #[GeneratedValue]
    public $id;

    /** @var string */
    #[Column(type: 'string', length: 255)]
    public $name = 'Bar';

    /** @var DDC1228Profile */
    #[OneToOne(targetEntity: 'DDC1228Profile')]
    public $profile;

    public function getProfile(): DDC1228Profile
    {
        return $this->profile;
    }
}

#[Entity]
class DDC1228Profile
{
    /** @var int */
    #[Id]
    #[Column(type: 'integer')]
    #[GeneratedValue]
    public $id;

    /** @var string */
    #[Column(type: 'string', length: 255)]
    public $name;

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }
}
