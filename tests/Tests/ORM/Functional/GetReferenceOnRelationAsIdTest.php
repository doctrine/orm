<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\ORM\EntityNotFoundException;
use Doctrine\Tests\Models\RelationAsId\Group;
use Doctrine\Tests\Models\RelationAsId\Membership;
use Doctrine\Tests\Models\RelationAsId\Profile;
use Doctrine\Tests\Models\RelationAsId\User;
use Doctrine\Tests\OrmFunctionalTestCase;
use Doctrine\Tests\Proxies\__CG__\Doctrine\Tests\Models\RelationAsId\Membership as MembershipProxy;
use Doctrine\Tests\Proxies\__CG__\Doctrine\Tests\Models\RelationAsId\User as UserProxy;

/**
 * @group pkfk
 */
class GetReferenceOnRelationAsIdTest extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        $this->useModelSet('relation_as_id');

        parent::setUp();

        $u       = new User();
        $u->id   = 1;
        $u->name = 'Athos';

        $p       = new Profile();
        $p->user = $u;
        $p->url  = 'https://example.com';

        $g       = new Group();
        $g->id   = 11;
        $g->name = 'Mousquetaires';

        $m        = new Membership();
        $m->user  = $u;
        $m->group = $g;
        $m->role  = 'cadet';

        $u2       = new User();
        $u2->id   = 2;
        $u2->name = 'Portos';

        $this->_em->persist($u);
        $this->_em->persist($p);
        $this->_em->persist($g);
        $this->_em->persist($m);
        $this->_em->persist($u2);
        $this->_em->flush();
        $this->_em->clear();
    }

    public function testCanGetByValue(): void
    {
        $profile = $this->_em->getReference(Profile::class, 1);

        self::assertInstanceOf(UserProxy::class, $profile->user);
        self::assertEquals('Athos', $profile->user->name);
    }

    public function testThrowsSensiblyIfNotFoundByValue(): void
    {
        self::markTestSkipped('unable');
        $profile = $this->_em->getReference(Profile::class, 999);

        $this->expectException(EntityNotFoundException::class);
        $profile->url;
    }

    public function testCanGetByRelatedEntity(): void
    {
        $user    = $this->_em->find(User::class, 1);
        $profile = $this->_em->getReference(Profile::class, $user);

        self::assertInstanceOf(User::class, $profile->user);
        self::assertEquals('Athos', $profile->user->name);
    }

    public function testThrowsSensiblyIfNotFoundByValidRelatedEntity(): void
    {
        self::markTestSkipped('unable');
        $user    = $this->_em->find(User::class, 2);
        $profile = $this->_em->getReference(Profile::class, $user);

        $this->expectException(EntityNotFoundException::class);
        $profile->url;
    }

    public function testThrowsSensiblyIfNotFoundByBrokenRelatedEntity(): void
    {
        self::markTestSkipped('unable');
        $user     = new User();
        $user->id = 999;
        $profile  = $this->_em->getReference(Profile::class, $user);

        $this->expectException(EntityNotFoundException::class);
        $profile->url;
    }

    public function testCanGetByRelatedProxy(): void
    {
        $user    = $this->_em->getReference(User::class, 1);
        $profile = $this->_em->getReference(Profile::class, $user);

        self::assertInstanceOf(UserProxy::class, $profile->user);
        self::assertEquals('Athos', $profile->user->name);
    }

    public function testThrowsSensiblyIfNotFoundByValidProxy(): void
    {
        self::markTestSkipped('unable');
        $user    = $this->_em->getReference(User::class, 2);
        $profile = $this->_em->getReference(Profile::class, $user);

        $this->expectException(EntityNotFoundException::class);
        $profile->url;
    }

    public function testThrowsSensiblyIfNotFoundByBrokenProxy(): void
    {
        self::markTestSkipped('unable');
        $user    = $this->_em->getReference(User::class, 999);
        $profile = $this->_em->getReference(Profile::class, $user);

        $this->expectException(EntityNotFoundException::class);
        $profile->url;
    }

    public function testCanGetOnCompositeIdByValueOrRelatedProxy(): void
    {
        $membership = $this->_em->getReference(Membership::class, [
            'user' => 1,
            'group' => 11,
        ]);

        self::assertInstanceOf(MembershipProxy::class, $membership);
        self::assertEquals('Mousquetaires', $membership->group->name);

        $this->_em->clear();

        $group      = $this->_em->getReference(Group::class, 11);
        $membership = $this->_em->getReference(Membership::class, [
            'user' => 1,
            'group' => $group,
        ]);

        self::assertInstanceOf(MembershipProxy::class, $membership);
        self::assertEquals('Athos', $membership->user->name);
    }

    public function testCanUpdateProperty(): void
    {
        $porthos = $this->_em->getReference(User::class, 2);

        $porthos->name = 'Porthos';
        $this->_em->persist($porthos);
        $this->_em->flush();
        $this->_em->clear();

        $fixedPorthos = $this->_em->find(User::class, 2);
        self::assertEquals('Porthos', $fixedPorthos->name);
    }

    public function testCanUpdateIdRegularId(): void
    {
        // "Proxy objects should be transparent to your code."
        self::markTestSkipped('not a regression?');

        $porthos     = $this->_em->getReference(User::class, 2);
        $porthos->id = 9;
        $this->_em->persist($porthos);
        $this->_em->flush();
        $this->_em->clear();

        $reindexedPorthos = $this->_em->find(User::class, 9);
        self::assertNotNull($reindexedPorthos);
    }

    public function testCanUpdateIdByRelatedProxy(): void
    {
        // "Proxy objects should be transparent to your code."
        self::markTestSkipped('not a regression?');

        $porthos       = $this->_em->getReference(User::class, 2);
        $profile       = $this->_em->getReference(Profile::class, 1);
        $profile->user = $porthos;
        $this->_em->persist($profile);
        $this->_em->flush();
        $this->_em->clear();

        $profile = $this->_em->find(Profile::class, 2);
        self::assertNotNull($profile);
    }

    public function testCanUpdateIdByEntity(): void
    {
        // "Proxy objects should be transparent to your code."
        self::markTestSkipped('not a regression?');

        $porthos       = $this->_em->find(User::class, 2);
        $profile       = $this->_em->getReference(Profile::class, 1);
        $profile->user = $porthos;
        $this->_em->persist($profile);
        $this->_em->flush();
        $this->_em->clear();

        $profile = $this->_em->find(Profile::class, 2);
        self::assertNotNull($profile);
    }

    public function testCanUpdateIdCompositeId(): void
    {
        // "Proxy objects should be transparent to your code."
        self::markTestSkipped('not a regression?');

        $membership = $this->_em->getReference(Membership::class, [
            'user' => 1,
            'group' => 11,
        ]);

        $g2                = new Group();
        $g2->id            = 12;
        $g2->name          = 'Ordre de la Jarretière';
        $membership->group = $g2;
        $this->_em->persist($g2);
        $this->_em->persist($membership);
        $this->_em->flush();
        $this->_em->clear();

        $membership = $this->_em->find(Membership::class, [
            'user' => 1,
            'group' => 12,
        ]);
        self::assertEquals('admin', $membership->role);
    }
}
