<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\EntityIdentityCollisionException;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\ORMInvalidArgumentException;
use Doctrine\ORM\PersistentCollection;
use Doctrine\ORM\Proxy\InternalProxy;
use Doctrine\ORM\Query;
use Doctrine\ORM\UnitOfWork;
use Doctrine\Tests\IterableTester;
use Doctrine\Tests\Models\CMS\CmsAddress;
use Doctrine\Tests\Models\CMS\CmsArticle;
use Doctrine\Tests\Models\CMS\CmsComment;
use Doctrine\Tests\Models\CMS\CmsGroup;
use Doctrine\Tests\Models\CMS\CmsPhonenumber;
use Doctrine\Tests\Models\CMS\CmsUser;
use Doctrine\Tests\OrmFunctionalTestCase;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Group;

class BasicFunctionalTest extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        $this->useModelSet('cms');

        parent::setUp();
    }

    public function testBasicUnitsOfWorkWithOneToManyAssociation(): void
    {
        // Create
        $user           = new CmsUser();
        $user->name     = 'Roman';
        $user->username = 'romanb';
        $user->status   = 'developer';
        $this->_em->persist($user);

        $this->_em->flush();

        self::assertIsNumeric($user->id);
        self::assertTrue($this->_em->contains($user));

        // Read
        $user2 = $this->_em->find(CmsUser::class, $user->id);
        self::assertSame($user, $user2);

        // Add a phonenumber
        $ph              = new CmsPhonenumber();
        $ph->phonenumber = '12345';
        $user->addPhonenumber($ph);
        $this->_em->flush();
        self::assertTrue($this->_em->contains($ph));
        self::assertTrue($this->_em->contains($user));

        // Update name
        $user->name = 'guilherme';
        $this->_em->flush();
        self::assertEquals('guilherme', $user->name);

        // Add another phonenumber
        $ph2              = new CmsPhonenumber();
        $ph2->phonenumber = '6789';
        $user->addPhonenumber($ph2);
        $this->_em->flush();
        self::assertTrue($this->_em->contains($ph2));

        // Delete
        $this->_em->remove($user);
        self::assertTrue($this->_em->getUnitOfWork()->isScheduledForDelete($user));
        self::assertTrue($this->_em->getUnitOfWork()->isScheduledForDelete($ph));
        self::assertTrue($this->_em->getUnitOfWork()->isScheduledForDelete($ph2));
        $this->_em->flush();
        self::assertFalse($this->_em->getUnitOfWork()->isScheduledForDelete($user));
        self::assertFalse($this->_em->getUnitOfWork()->isScheduledForDelete($ph));
        self::assertFalse($this->_em->getUnitOfWork()->isScheduledForDelete($ph2));
        self::assertEquals(UnitOfWork::STATE_NEW, $this->_em->getUnitOfWork()->getEntityState($user));
        self::assertEquals(UnitOfWork::STATE_NEW, $this->_em->getUnitOfWork()->getEntityState($ph));
        self::assertEquals(UnitOfWork::STATE_NEW, $this->_em->getUnitOfWork()->getEntityState($ph2));
    }

    public function testOneToManyAssociationModification(): void
    {
        $user           = new CmsUser();
        $user->name     = 'Roman';
        $user->username = 'romanb';
        $user->status   = 'developer';

        $ph1              = new CmsPhonenumber();
        $ph1->phonenumber = '0301234';
        $ph2              = new CmsPhonenumber();
        $ph2->phonenumber = '987654321';

        $user->addPhonenumber($ph1);
        $user->addPhonenumber($ph2);

        $this->_em->persist($user);
        $this->_em->flush();

        // Remove the first element from the collection
        unset($user->phonenumbers[0]);
        $ph1->user = null; // owning side!

        $this->_em->flush();

        self::assertCount(1, $user->phonenumbers);
        self::assertNull($ph1->user);
    }

    public function testBasicOneToOne(): void
    {
        $user           = new CmsUser();
        $user->name     = 'Roman';
        $user->username = 'romanb';
        $user->status   = 'developer';

        $address          = new CmsAddress();
        $address->country = 'Germany';
        $address->city    = 'Berlin';
        $address->zip     = '12345';

        $user->address = $address; // inverse side
        $address->user = $user; // owning side!

        $this->_em->persist($user);
        $this->_em->flush();

        // Check that the foreign key has been set
        $userId = $this->_em->getConnection()->executeQuery(
            'SELECT user_id FROM cms_addresses WHERE id=?',
            [$address->id],
        )->fetchOne();
        self::assertIsNumeric($userId);

        $this->_em->clear();

        $user2 = $this->_em->createQuery('select u from \Doctrine\Tests\Models\CMS\CmsUser u where u.id=?1')
                ->setParameter(1, $userId)
                ->getSingleResult();

        // Address has been eager-loaded because it cant be lazy
        self::assertInstanceOf(CmsAddress::class, $user2->address);
        self::assertFalse($this->isUninitializedObject($user2->address));
    }

    #[Group('DDC-1230')]
    public function testRemove(): void
    {
        $user           = new CmsUser();
        $user->name     = 'Guilherme';
        $user->username = 'gblanco';
        $user->status   = 'developer';

        self::assertEquals(UnitOfWork::STATE_NEW, $this->_em->getUnitOfWork()->getEntityState($user), 'State should be UnitOfWork::STATE_NEW');

        $this->_em->persist($user);

        self::assertEquals(UnitOfWork::STATE_MANAGED, $this->_em->getUnitOfWork()->getEntityState($user), 'State should be UnitOfWork::STATE_MANAGED');

        $this->_em->remove($user);

        self::assertEquals(UnitOfWork::STATE_NEW, $this->_em->getUnitOfWork()->getEntityState($user), 'State should be UnitOfWork::STATE_NEW');

        $this->_em->persist($user);
        $this->_em->flush();
        $id = $user->getId();

        $this->_em->remove($user);

        self::assertEquals(UnitOfWork::STATE_REMOVED, $this->_em->getUnitOfWork()->getEntityState($user), 'State should be UnitOfWork::STATE_REMOVED');
        $this->_em->flush();

        self::assertEquals(UnitOfWork::STATE_NEW, $this->_em->getUnitOfWork()->getEntityState($user), 'State should be UnitOfWork::STATE_NEW');

        self::assertNull($this->_em->find(CmsUser::class, $id));
    }

    public function testOneToManyOrphanRemoval(): void
    {
        $user           = new CmsUser();
        $user->name     = 'Guilherme';
        $user->username = 'gblanco';
        $user->status   = 'developer';

        for ($i = 0; $i < 3; ++$i) {
            $phone              = new CmsPhonenumber();
            $phone->phonenumber = 100 + $i;
            $user->addPhonenumber($phone);
        }

        $this->_em->persist($user);

        $this->_em->flush();

        $user->getPhonenumbers()->remove(0);
        self::assertCount(2, $user->getPhonenumbers());

        $this->_em->flush();

        // Check that there are just 2 phonenumbers left
        $count = $this->_em->getConnection()->fetchOne('SELECT COUNT(*) FROM cms_phonenumbers');
        self::assertEquals(2, $count); // only 2 remaining

        // check that clear() removes the others via orphan removal
        $user->getPhonenumbers()->clear();
        $this->_em->flush();
        self::assertEquals(0, $this->_em->getConnection()->fetchOne('select count(*) from cms_phonenumbers'));
    }

    public function testBasicQuery(): void
    {
        $user           = new CmsUser();
        $user->name     = 'Guilherme';
        $user->username = 'gblanco';
        $user->status   = 'developer';
        $this->_em->persist($user);
        $this->_em->flush();

        $query = $this->_em->createQuery('select u from Doctrine\Tests\Models\CMS\CmsUser u');

        $users = $query->getResult();

        self::assertCount(1, $users);
        self::assertEquals('Guilherme', $users[0]->name);
        self::assertEquals('gblanco', $users[0]->username);
        self::assertEquals('developer', $users[0]->status);
        //$this->assertNull($users[0]->phonenumbers);
        //$this->assertNull($users[0]->articles);

        $this->_em->clear();

        IterableTester::assertResultsAreTheSame($query);

        $usersArray = $query->getArrayResult();

        self::assertIsArray($usersArray);
        self::assertCount(1, $usersArray);
        self::assertEquals('Guilherme', $usersArray[0]['name']);
        self::assertEquals('gblanco', $usersArray[0]['username']);
        self::assertEquals('developer', $usersArray[0]['status']);

        $usersScalar = $query->getScalarResult();

        self::assertIsArray($usersScalar);
        self::assertCount(1, $usersScalar);
        self::assertEquals('Guilherme', $usersScalar[0]['u_name']);
        self::assertEquals('gblanco', $usersScalar[0]['u_username']);
        self::assertEquals('developer', $usersScalar[0]['u_status']);
    }

    public function testSingleColumnQuery(): void
    {
        $gregoire           = new CmsUser();
        $gregoire->name     = 'Gregoire';
        $gregoire->username = 'greg0ire';
        $gregoire->status   = 'developer';
        $this->_em->persist($gregoire);

        $bhushan           = new CmsUser();
        $bhushan->name     = 'Bhushan';
        $bhushan->username = 'bhushan';
        $bhushan->status   = 'developer';
        $this->_em->persist($bhushan);

        $this->_em->flush();

        $query = $this->_em->createQuery('select u.username from Doctrine\Tests\Models\CMS\CmsUser u order by u.username DESC');

        $users = $query->getSingleColumnResult();

        $expected = [
            'greg0ire',
            'bhushan',
        ];

        self::assertCount(2, $users);
        self::assertSame($expected, $users);
    }

    public function testBasicOneToManyInnerJoin(): void
    {
        $user           = new CmsUser();
        $user->name     = 'Guilherme';
        $user->username = 'gblanco';
        $user->status   = 'developer';
        $this->_em->persist($user);
        $this->_em->flush();

        $query = $this->_em->createQuery('select u from Doctrine\Tests\Models\CMS\CmsUser u join u.phonenumbers p');

        $users = $query->getResult();

        self::assertCount(0, $users);
    }

    public function testBasicOneToManyLeftJoin(): void
    {
        $user           = new CmsUser();
        $user->name     = 'Guilherme';
        $user->username = 'gblanco';
        $user->status   = 'developer';
        $this->_em->persist($user);
        $this->_em->flush();

        $query = $this->_em->createQuery('select u,p from Doctrine\Tests\Models\CMS\CmsUser u left join u.phonenumbers p');

        $users = $query->getResult();

        self::assertCount(1, $users);
        self::assertEquals('Guilherme', $users[0]->name);
        self::assertEquals('gblanco', $users[0]->username);
        self::assertEquals('developer', $users[0]->status);
        self::assertInstanceOf(PersistentCollection::class, $users[0]->phonenumbers);
        self::assertTrue($users[0]->phonenumbers->isInitialized());
        self::assertEquals(0, $users[0]->phonenumbers->count());
    }

    public function testBasicRefresh(): void
    {
        $user           = new CmsUser();
        $user->name     = 'Guilherme';
        $user->username = 'gblanco';
        $user->status   = 'developer';

        $this->_em->persist($user);
        $this->_em->flush();

        $user->status = 'mascot';

        self::assertEquals('mascot', $user->status);
        $this->_em->refresh($user);
        self::assertEquals('developer', $user->status);
    }

    #[Group('DDC-833')]
    public function testRefreshResetsCollection(): void
    {
        $user           = new CmsUser();
        $user->name     = 'Guilherme';
        $user->username = 'gblanco';
        $user->status   = 'developer';

        // Add a phonenumber
        $ph1              = new CmsPhonenumber();
        $ph1->phonenumber = '12345';
        $user->addPhonenumber($ph1);

        // Add a phonenumber
        $ph2              = new CmsPhonenumber();
        $ph2->phonenumber = '54321';

        $this->_em->persist($user);
        $this->_em->persist($ph1);
        $this->_em->persist($ph2);
        $this->_em->flush();

        $user->addPhonenumber($ph2);

        self::assertCount(2, $user->phonenumbers);
        $this->_em->refresh($user);

        self::assertCount(1, $user->phonenumbers);
    }

    #[Group('DDC-833')]
    public function testDqlRefreshResetsCollection(): void
    {
        $user           = new CmsUser();
        $user->name     = 'Guilherme';
        $user->username = 'gblanco';
        $user->status   = 'developer';

        // Add a phonenumber
        $ph1              = new CmsPhonenumber();
        $ph1->phonenumber = '12345';
        $user->addPhonenumber($ph1);

        // Add a phonenumber
        $ph2              = new CmsPhonenumber();
        $ph2->phonenumber = '54321';

        $this->_em->persist($user);
        $this->_em->persist($ph1);
        $this->_em->persist($ph2);
        $this->_em->flush();

        $user->addPhonenumber($ph2);

        self::assertCount(2, $user->phonenumbers);
        $dql  = 'SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE u.id = ?1';
        $user = $this->_em->createQuery($dql)
                          ->setParameter(1, $user->id)
                          ->setHint(Query::HINT_REFRESH, true)
                          ->getSingleResult();

        self::assertCount(1, $user->phonenumbers);
    }

    #[Group('DDC-833')]
    public function testCreateEntityOfProxy(): void
    {
        $user           = new CmsUser();
        $user->name     = 'Guilherme';
        $user->username = 'gblanco';
        $user->status   = 'developer';

        // Add a phonenumber
        $ph1              = new CmsPhonenumber();
        $ph1->phonenumber = '12345';
        $user->addPhonenumber($ph1);

        // Add a phonenumber
        $ph2              = new CmsPhonenumber();
        $ph2->phonenumber = '54321';

        $this->_em->persist($user);
        $this->_em->persist($ph1);
        $this->_em->persist($ph2);
        $this->_em->flush();
        $this->_em->clear();

        $userId = $user->id;
        $user   = $this->_em->getReference(CmsUser::class, $user->id);

        $dql  = 'SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE u.id = ?1';
        $user = $this->_em->createQuery($dql)
                          ->setParameter(1, $userId)
                          ->getSingleResult();

        self::assertCount(1, $user->phonenumbers);
    }

    public function testAddToCollectionDoesNotInitialize(): void
    {
        $user           = new CmsUser();
        $user->name     = 'Guilherme';
        $user->username = 'gblanco';
        $user->status   = 'developer';

        for ($i = 0; $i < 3; ++$i) {
            $phone              = new CmsPhonenumber();
            $phone->phonenumber = 100 + $i;
            $user->addPhonenumber($phone);
        }

        $this->_em->persist($user);
        $this->_em->flush();
        $this->_em->clear();

        self::assertEquals(3, $user->getPhonenumbers()->count());

        $query = $this->_em->createQuery("select u from Doctrine\Tests\Models\CMS\CmsUser u where u.username='gblanco'");

        $gblanco = $query->getSingleResult();

        self::assertFalse($gblanco->getPhonenumbers()->isInitialized());

        $newPhone              = new CmsPhonenumber();
        $newPhone->phonenumber = 555;
        $gblanco->addPhonenumber($newPhone);

        self::assertFalse($gblanco->getPhonenumbers()->isInitialized());
        $this->_em->persist($gblanco);

        $this->_em->flush();
        $this->_em->clear();

        $query    = $this->_em->createQuery("select u, p from Doctrine\Tests\Models\CMS\CmsUser u join u.phonenumbers p where u.username='gblanco'");
        $gblanco2 = $query->getSingleResult();
        self::assertEquals(4, $gblanco2->getPhonenumbers()->count());
    }

    public function testInitializeCollectionWithNewObjectsRetainsNewObjects(): void
    {
        $user           = new CmsUser();
        $user->name     = 'Guilherme';
        $user->username = 'gblanco';
        $user->status   = 'developer';

        for ($i = 0; $i < 3; ++$i) {
            $phone              = new CmsPhonenumber();
            $phone->phonenumber = 100 + $i;
            $user->addPhonenumber($phone);
        }

        $this->_em->persist($user);
        $this->_em->flush();
        $this->_em->clear();

        self::assertEquals(3, $user->getPhonenumbers()->count());

        $query = $this->_em->createQuery("select u from Doctrine\Tests\Models\CMS\CmsUser u where u.username='gblanco'");

        $gblanco = $query->getSingleResult();

        self::assertFalse($gblanco->getPhonenumbers()->isInitialized());

        $newPhone              = new CmsPhonenumber();
        $newPhone->phonenumber = 555;
        $gblanco->addPhonenumber($newPhone);

        self::assertFalse($gblanco->getPhonenumbers()->isInitialized());
        self::assertEquals(4, $gblanco->getPhonenumbers()->count());
        self::assertTrue($gblanco->getPhonenumbers()->isInitialized());

        $this->_em->flush();
        $this->_em->clear();

        $query    = $this->_em->createQuery("select u, p from Doctrine\Tests\Models\CMS\CmsUser u join u.phonenumbers p where u.username='gblanco'");
        $gblanco2 = $query->getSingleResult();
        self::assertEquals(4, $gblanco2->getPhonenumbers()->count());
    }

    public function testSetToOneAssociationWithGetReference(): void
    {
        $user           = new CmsUser();
        $user->name     = 'Guilherme';
        $user->username = 'gblanco';
        $user->status   = 'developer';
        $this->_em->persist($user);

        $address          = new CmsAddress();
        $address->country = 'Germany';
        $address->city    = 'Berlin';
        $address->zip     = '12345';
        $this->_em->persist($address);

        $this->_em->flush();
        $userId = $user->getId();
        $this->_em->clear();
        $user = $this->_em->find(CmsUser::class, $userId);

        // Assume we only got the identifier of the address and now want to attach
        // that address to the user without actually loading it, using getReference().
        $addressRef = $this->_em->getReference(CmsAddress::class, $address->getId());

        $user->setAddress($addressRef); // Ugh! Initializes address 'cause of $address->setUser($user)!

        $this->_em->flush();
        $this->_em->clear();

        // Assume we only got the identifier of the user and now want to attach
        // the article to the user without actually loading it, using getReference().
        $userRef = $this->_em->getReference(CmsUser::class, $user->getId());
        self::assertTrue($this->isUninitializedObject($userRef));

        $article        = new CmsArticle();
        $article->topic = 'topic';
        $article->text  = 'text';
        $article->setAuthor($userRef);

        $this->_em->persist($article);
        $this->_em->flush();

        self::assertFalse($userRef->__isInitialized());

        $this->_em->clear();

        // Check with a fresh load that the association is indeed there
        $query   = $this->_em->createQuery("select u, a from Doctrine\Tests\Models\CMS\CmsUser u join u.articles a where u.username='gblanco'");
        $gblanco = $query->getSingleResult();

        self::assertInstanceOf(CmsUser::class, $gblanco);
        self::assertInstanceOf(CmsArticle::class, $gblanco->articles[0]);
        self::assertSame($article->id, $gblanco->articles[0]->id);
        self::assertSame('text', $gblanco->articles[0]->text);
    }

    public function testAddToToManyAssociationWithGetReference(): void
    {
        $group       = new CmsGroup();
        $group->name = 'admins';
        $this->_em->persist($group);
        $this->_em->flush();
        $this->_em->clear();

        // Assume we only got the identifier of the user and now want to attach
        // the article to the user without actually loading it, using getReference().
        $groupRef = $this->_em->getReference(CmsGroup::class, $group->id);
        self::assertTrue($this->isUninitializedObject($groupRef));

        $user           = new CmsUser();
        $user->name     = 'Guilherme';
        $user->username = 'gblanco';
        $user->groups->add($groupRef);

        $this->_em->persist($user);
        $this->_em->flush();

        self::assertFalse($groupRef->__isInitialized());

        $this->_em->clear();

        // Check with a fresh load that the association is indeed there
        $query   = $this->_em->createQuery("select u, a from Doctrine\Tests\Models\CMS\CmsUser u join u.groups a where u.username='gblanco'");
        $gblanco = $query->getSingleResult();

        self::assertInstanceOf(CmsUser::class, $gblanco);
        self::assertInstanceOf(CmsGroup::class, $gblanco->groups[0]);
        self::assertSame($group->id, $gblanco->groups[0]->id);
        self::assertSame('admins', $gblanco->groups[0]->name);
    }

    public function testOneToManyCascadeRemove(): void
    {
        $user           = new CmsUser();
        $user->name     = 'Guilherme';
        $user->username = 'gblanco';
        $user->status   = 'developer';

        for ($i = 0; $i < 3; ++$i) {
            $phone              = new CmsPhonenumber();
            $phone->phonenumber = 100 + $i;
            $user->addPhonenumber($phone);
        }

        $this->_em->persist($user);
        $this->_em->flush();
        $this->_em->clear();

        $query   = $this->_em->createQuery("select u from Doctrine\Tests\Models\CMS\CmsUser u where u.username='gblanco'");
        $gblanco = $query->getSingleResult();

        $this->_em->remove($gblanco);
        $this->_em->flush();

        $this->_em->clear();

        self::assertEquals(0, $this->_em->createQuery(
            'select count(p.phonenumber) from Doctrine\Tests\Models\CMS\CmsPhonenumber p',
        )
                ->getSingleScalarResult());

        self::assertEquals(0, $this->_em->createQuery(
            'select count(u.id) from Doctrine\Tests\Models\CMS\CmsUser u',
        )
                ->getSingleScalarResult());
    }

    public function testTextColumnSaveAndRetrieve(): void
    {
        $user           = new CmsUser();
        $user->name     = 'Guilherme';
        $user->username = 'gblanco';
        $user->status   = 'developer';

        $this->_em->persist($user);

        $article        = new CmsArticle();
        $article->text  = 'Lorem ipsum dolor sunt.';
        $article->topic = 'A Test Article!';
        $article->setAuthor($user);

        $this->_em->persist($article);
        $this->_em->flush();
        $articleId = $article->id;

        $this->_em->clear();

        // test find() with leading backslash at the same time
        $articleNew = $this->_em->find(CmsArticle::class, $articleId);
        self::assertTrue($this->_em->contains($articleNew));
        self::assertEquals('Lorem ipsum dolor sunt.', $articleNew->text);

        self::assertNotSame($article, $articleNew);

        $articleNew->text = 'Lorem ipsum dolor sunt. And stuff!';

        $this->_em->flush();
        $this->_em->clear();

        $articleNew = $this->_em->find(CmsArticle::class, $articleId);
        self::assertEquals('Lorem ipsum dolor sunt. And stuff!', $articleNew->text);
        self::assertTrue($this->_em->contains($articleNew));
    }

    public function testFlushDoesNotIssueUnnecessaryUpdates(): void
    {
        $user           = new CmsUser();
        $user->name     = 'Guilherme';
        $user->username = 'gblanco';
        $user->status   = 'developer';

        $address          = new CmsAddress();
        $address->country = 'Germany';
        $address->city    = 'Berlin';
        $address->zip     = '12345';

        $address->user = $user;
        $user->address = $address;

        $article        = new CmsArticle();
        $article->text  = 'Lorem ipsum dolor sunt.';
        $article->topic = 'A Test Article!';
        $article->setAuthor($user);

        $this->_em->persist($article);
        $this->_em->persist($user);

        $this->_em->flush();
        $this->_em->clear();

        $query = $this->_em->createQuery('select u,a,ad from Doctrine\Tests\Models\CMS\CmsUser u join u.articles a join u.address ad');
        $user2 = $query->getSingleResult();

        self::assertCount(1, $user2->articles);
        self::assertInstanceOf(CmsAddress::class, $user2->address);

        $this->getQueryLog()->reset()->enable();
        $this->_em->flush();

        $this->assertQueryCount(0);
    }

    public function testRemoveEntityByReference(): void
    {
        $user           = new CmsUser();
        $user->name     = 'Guilherme';
        $user->username = 'gblanco';
        $user->status   = 'developer';

        $this->_em->persist($user);
        $this->_em->flush();
        $this->_em->clear();

        $userRef = $this->_em->getReference(CmsUser::class, $user->getId());
        $this->_em->remove($userRef);
        $this->_em->flush();
        $this->_em->clear();

        self::assertEquals(0, $this->_em->getConnection()->fetchOne('select count(*) from cms_users'));
    }

    public function testQueryEntityByReference(): void
    {
        $user           = new CmsUser();
        $user->name     = 'Guilherme';
        $user->username = 'gblanco';
        $user->status   = 'developer';

        $address          = new CmsAddress();
        $address->country = 'Germany';
        $address->city    = 'Berlin';
        $address->zip     = '12345';

        $user->setAddress($address);

        $this->_em->wrapInTransaction(static function (EntityManagerInterface $em) use ($user): void {
            $em->persist($user);
        });
        $this->_em->clear();

        $userRef  = $this->_em->getReference(CmsUser::class, $user->getId());
        $address2 = $this->_em->createQuery('select a from Doctrine\Tests\Models\CMS\CmsAddress a where a.user = :user')
                ->setParameter('user', $userRef)
                ->getSingleResult();

        self::assertTrue($userRef === $address2->getUser());
        self::assertTrue($this->isUninitializedObject($userRef));
        self::assertEquals('Germany', $address2->country);
        self::assertEquals('Berlin', $address2->city);
        self::assertEquals('12345', $address2->zip);
    }

    public function testOneToOneNullUpdate(): void
    {
        $user           = new CmsUser();
        $user->username = 'beberlei';
        $user->name     = 'Benjamin E.';
        $user->status   = 'active';

        $address          = new CmsAddress();
        $address->city    = 'Bonn';
        $address->zip     = '12354';
        $address->country = 'Germany';
        $address->street  = 'somestreet';
        $address->user    = $user;

        $this->_em->persist($address);
        $this->_em->persist($user);
        $this->_em->flush();

        self::assertEquals(1, $this->_em->getConnection()->fetchOne('select 1 from cms_addresses where user_id = ' . $user->id));

        $address->user = null;
        $this->_em->flush();

        self::assertNotEquals(1, $this->_em->getConnection()->fetchOne('select 1 from cms_addresses where user_id = ' . $user->id));
    }

    #[Group('DDC-600')]
    #[Group('DDC-455')]
    public function testNewAssociatedEntityDuringFlushThrowsException(): void
    {
        $user           = new CmsUser();
        $user->username = 'beberlei';
        $user->name     = 'Benjamin E.';
        $user->status   = 'active';

        $address          = new CmsAddress();
        $address->city    = 'Bonn';
        $address->zip     = '12354';
        $address->country = 'Germany';
        $address->street  = 'somestreet';
        $address->user    = $user;

        $this->_em->persist($address);

        // flushing without persisting $user should raise an exception
        $this->expectException(InvalidArgumentException::class);
        $this->_em->flush();
    }

    #[Group('DDC-600')]
    #[Group('DDC-455')]
    public function testNewAssociatedEntityDuringFlushThrowsException2(): void
    {
        $user           = new CmsUser();
        $user->username = 'beberlei';
        $user->name     = 'Benjamin E.';
        $user->status   = 'active';

        $address          = new CmsAddress();
        $address->city    = 'Bonn';
        $address->zip     = '12354';
        $address->country = 'Germany';
        $address->street  = 'somestreet';
        $address->user    = $user;

        $this->_em->persist($address);
        $this->_em->persist($user);
        $this->_em->flush();

        $u2            = new CmsUser();
        $u2->username  = 'beberlei';
        $u2->name      = 'Benjamin E.';
        $u2->status    = 'inactive';
        $address->user = $u2;

        // flushing without persisting $u2 should raise an exception
        $this->expectException(InvalidArgumentException::class);
        $this->_em->flush();
    }

    #[Group('DDC-600')]
    #[Group('DDC-455')]
    public function testNewAssociatedEntityDuringFlushThrowsException3(): void
    {
        $art        = new CmsArticle();
        $art->topic = 'topic';
        $art->text  = 'the text';

        $com        = new CmsComment();
        $com->topic = 'Good';
        $com->text  = 'Really good!';
        $art->addComment($com);

        $this->_em->persist($art);

        // flushing without persisting $com should raise an exception
        $this->expectException(InvalidArgumentException::class);
        $this->_em->flush();
    }

    public function testOneToOneOrphanRemoval(): void
    {
        $user           = new CmsUser();
        $user->username = 'beberlei';
        $user->name     = 'Benjamin E.';
        $user->status   = 'active';

        $address          = new CmsAddress();
        $address->city    = 'Bonn';
        $address->zip     = '12354';
        $address->country = 'Germany';
        $address->street  = 'somestreet';
        $address->user    = $user;
        $user->address    = $address;

        $this->_em->persist($address);
        $this->_em->persist($user);
        $this->_em->flush();
        $addressId = $address->getId();

        $user->address = null;

        $this->_em->flush();

        self::assertEquals(0, $this->_em->getConnection()->fetchOne('select count(*) from cms_addresses'));

        // check orphan removal through replacement
        $user->address = $address;
        $address->user = $user;

        $this->_em->flush();
        self::assertEquals(1, $this->_em->getConnection()->fetchOne('select count(*) from cms_addresses'));

        // remove $address to free up unique key id
        $this->_em->remove($address);
        $this->_em->flush();

        $newAddress          = new CmsAddress();
        $newAddress->city    = 'NewBonn';
        $newAddress->zip     = '12354';
        $newAddress->country = 'NewGermany';
        $newAddress->street  = 'somenewstreet';
        $newAddress->user    = $user;
        $user->address       = $newAddress;

        $this->_em->flush();
        self::assertEquals(1, $this->_em->getConnection()->fetchOne('select count(*) from cms_addresses'));
    }

    #[Group('DDC-952')]
    public function testManyToOneFetchModeQuery(): void
    {
        $user           = new CmsUser();
        $user->username = 'beberlei';
        $user->name     = 'Benjamin E.';
        $user->status   = 'active';

        $article        = new CmsArticle();
        $article->topic = 'foo';
        $article->text  = 'bar';
        $article->user  = $user;

        $this->_em->persist($article);
        $this->_em->persist($user);
        $this->_em->flush();
        $this->_em->clear();

        $this->getQueryLog()->reset()->enable();
        $dql     = 'SELECT a FROM Doctrine\Tests\Models\CMS\CmsArticle a WHERE a.id = ?1';
        $article = $this->_em->createQuery($dql)
                             ->setParameter(1, $article->id)
                             ->setFetchMode(CmsArticle::class, 'user', ClassMetadata::FETCH_EAGER)
                             ->getSingleResult();
        self::assertInstanceOf(InternalProxy::class, $article->user, 'It IS a proxy, ...');
        self::assertFalse($this->isUninitializedObject($article->user), '...but its initialized!');
        $this->assertQueryCount(2);
    }

    #[Group('DDC-720')]
    public function testFlushSingleManagedEntity(): void
    {
        $user           = new CmsUser();
        $user->name     = 'Dominik';
        $user->username = 'domnikl';
        $user->status   = 'developer';

        $this->_em->persist($user);
        $this->_em->flush();

        $user->status = 'administrator';
        $this->_em->flush();
        $this->_em->clear();

        $user = $this->_em->find($user::class, $user->id);
        self::assertEquals('administrator', $user->status);
    }

    #[Group('DDC-720')]
    public function testFlushAndCascadePersist(): void
    {
        $user           = new CmsUser();
        $user->name     = 'Dominik';
        $user->username = 'domnikl';
        $user->status   = 'developer';

        $this->_em->persist($user);
        $this->_em->flush();

        $address          = new CmsAddress();
        $address->city    = 'Springfield';
        $address->zip     = '12354';
        $address->country = 'Germany';
        $address->street  = 'Foo Street';
        $address->user    = $user;
        $user->address    = $address;

        $this->_em->flush();

        self::assertTrue($this->_em->contains($address), 'Other user is contained in EntityManager');
        self::assertTrue($address->id > 0, 'other user has an id');
    }

    #[Group('DDC-720')]
    public function testFlushSingleAndNoCascade(): void
    {
        $user           = new CmsUser();
        $user->name     = 'Dominik';
        $user->username = 'domnikl';
        $user->status   = 'developer';

        $this->_em->persist($user);
        $this->_em->flush();

        $article1         = new CmsArticle();
        $article1->topic  = 'Foo';
        $article1->text   = 'Foo Text';
        $article1->user   = $user;
        $user->articles[] = $article1;

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("A new entity was found through the relationship 'Doctrine\Tests\Models\CMS\CmsUser#articles'");

        $this->_em->flush();
    }

    #[Group('DDC-720')]
    #[Group('DDC-1612')]
    #[Group('DDC-2267')]
    public function testFlushSingleNewEntityThenRemove(): void
    {
        $user           = new CmsUser();
        $user->name     = 'Dominik';
        $user->username = 'domnikl';
        $user->status   = 'developer';

        $this->_em->persist($user);
        $this->_em->flush();

        $userId = $user->id;

        $this->_em->remove($user);
        $this->_em->flush();
        $this->_em->clear();

        self::assertNull($this->_em->find($user::class, $userId));
    }

    #[Group('DDC-1585')]
    public function testWrongAssociationInstance(): void
    {
        $user           = new CmsUser();
        $user->name     = 'Dominik';
        $user->username = 'domnikl';
        $user->status   = 'developer';
        $user->address  = $user;

        $this->expectException(ORMInvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Expected value of type "Doctrine\Tests\Models\CMS\CmsAddress" for association field ' .
            '"Doctrine\Tests\Models\CMS\CmsUser#$address", got "Doctrine\Tests\Models\CMS\CmsUser" instead.',
        );

        $this->_em->persist($user);

        $this->_em->flush();
    }

    public function testItThrowsWhenReferenceUsesIdAssignedByDatabase(): void
    {
        $user           = new CmsUser();
        $user->name     = 'test';
        $user->username = 'test';
        $this->_em->persist($user);
        $this->_em->flush();

        // Obtain a reference object for the next ID. This is a user error - references
        // should be fetched only for existing IDs
        $ref = $this->_em->getReference(CmsUser::class, $user->id + 1);

        $user2           = new CmsUser();
        $user2->name     = 'test2';
        $user2->username = 'test2';

        // Now the database will assign an ID to the $user2 entity, but that place
        // in the identity map is already taken by user error.
        $this->expectException(EntityIdentityCollisionException::class);
        $this->expectExceptionMessageMatches('/another object .* was already present for the same ID/');

        // depending on ID generation strategy, the ID may be asssigned already here
        // and the entity be put in the identity map
        $this->_em->persist($user2);

        // post insert IDs will be assigned during flush
        $this->_em->flush();
    }
}
