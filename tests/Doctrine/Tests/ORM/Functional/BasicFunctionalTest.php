<?php

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\ORM\Query;
use Doctrine\Tests\Models\CMS\CmsUser;
use Doctrine\Tests\Models\CMS\CmsPhonenumber;
use Doctrine\Tests\Models\CMS\CmsAddress;
use Doctrine\Tests\Models\CMS\CmsGroup;
use Doctrine\Tests\Models\CMS\CmsArticle;
use Doctrine\Tests\Models\CMS\CmsComment;

require_once __DIR__ . '/../../TestInit.php';

class BasicFunctionalTest extends \Doctrine\Tests\OrmFunctionalTestCase
{
    protected function setUp()
    {
        $this->useModelSet('cms');
        parent::setUp();
    }

    public function testBasicUnitsOfWorkWithOneToManyAssociation()
    {
        // Create
        $user = new CmsUser;
        $user->name = 'Roman';
        $user->username = 'romanb';
        $user->status = 'developer';
        $this->_em->persist($user);
       
        $this->_em->flush();
        
        $this->assertTrue(is_numeric($user->id));
        $this->assertTrue($this->_em->contains($user));

        // Read
        $user2 = $this->_em->find('Doctrine\Tests\Models\CMS\CmsUser', $user->id);
        $this->assertTrue($user === $user2);

        // Add a phonenumber
        $ph = new CmsPhonenumber;
        $ph->phonenumber = "12345";
        $user->addPhonenumber($ph);
        $this->_em->flush();
        $this->assertTrue($this->_em->contains($ph));
        $this->assertTrue($this->_em->contains($user));
        //$this->assertTrue($user->phonenumbers instanceof \Doctrine\ORM\PersistentCollection);

        // Update name
        $user->name = 'guilherme';
        $this->_em->flush();
        $this->assertEquals('guilherme', $user->name);

        // Add another phonenumber
        $ph2 = new CmsPhonenumber;
        $ph2->phonenumber = "6789";
        $user->addPhonenumber($ph2);
        $this->_em->flush();
        $this->assertTrue($this->_em->contains($ph2));

        // Delete
        $this->_em->remove($user);
        $this->assertTrue($this->_em->getUnitOfWork()->isScheduledForDelete($user));
        $this->assertTrue($this->_em->getUnitOfWork()->isScheduledForDelete($ph));
        $this->assertTrue($this->_em->getUnitOfWork()->isScheduledForDelete($ph2));
        $this->_em->flush();
        $this->assertFalse($this->_em->getUnitOfWork()->isScheduledForDelete($user));
        $this->assertFalse($this->_em->getUnitOfWork()->isScheduledForDelete($ph));
        $this->assertFalse($this->_em->getUnitOfWork()->isScheduledForDelete($ph2));
        $this->assertEquals(\Doctrine\ORM\UnitOfWork::STATE_NEW, $this->_em->getUnitOfWork()->getEntityState($user));
        $this->assertEquals(\Doctrine\ORM\UnitOfWork::STATE_NEW, $this->_em->getUnitOfWork()->getEntityState($ph));
        $this->assertEquals(\Doctrine\ORM\UnitOfWork::STATE_NEW, $this->_em->getUnitOfWork()->getEntityState($ph2));
    }

    public function testOneToManyAssociationModification()
    {
        $user = new CmsUser;
        $user->name = 'Roman';
        $user->username = 'romanb';
        $user->status = 'developer';

        $ph1 = new CmsPhonenumber;
        $ph1->phonenumber = "0301234";
        $ph2 = new CmsPhonenumber;
        $ph2->phonenumber = "987654321";

        $user->addPhonenumber($ph1);
        $user->addPhonenumber($ph2);

        $this->_em->persist($user);
        $this->_em->flush();

        //$this->assertTrue($user->phonenumbers instanceof \Doctrine\ORM\PersistentCollection);

        // Remove the first element from the collection
        unset($user->phonenumbers[0]);
        $ph1->user = null; // owning side!

        $this->_em->flush();

        $this->assertEquals(1, count($user->phonenumbers));
        $this->assertNull($ph1->user);
    }

    public function testBasicOneToOne()
    {
        //$this->_em->getConnection()->getConfiguration()->setSQLLogger(new \Doctrine\DBAL\Logging\EchoSQLLogger);
        $user = new CmsUser;
        $user->name = 'Roman';
        $user->username = 'romanb';
        $user->status = 'developer';

        $address = new CmsAddress;
        $address->country = 'Germany';
        $address->city = 'Berlin';
        $address->zip = '12345';

        $user->address = $address; // inverse side
        $address->user = $user; // owning side!

        $this->_em->persist($user);
        $this->_em->flush();

        // Check that the foreign key has been set
        $userId = $this->_em->getConnection()->executeQuery(
            "SELECT user_id FROM cms_addresses WHERE id=?", array($address->id)
        )->fetchColumn();
        $this->assertTrue(is_numeric($userId));
        
        $this->_em->clear();
        
        $user2 = $this->_em->createQuery('select u from \Doctrine\Tests\Models\CMS\CmsUser u where u.id=?1')
                ->setParameter(1, $userId)
                ->getSingleResult();
        
        // Address has been eager-loaded because it cant be lazy
        $this->assertTrue($user2->address instanceof CmsAddress);
        $this->assertFalse($user2->address instanceof \Doctrine\ORM\Proxy\Proxy);
    }
    
    /**
     * @group DDC-1230
     */
    public function testRemove()
    {
        $user = new CmsUser;
        $user->name = 'Guilherme';
        $user->username = 'gblanco';
        $user->status = 'developer';

        $this->assertEquals(\Doctrine\ORM\UnitOfWork::STATE_NEW, $this->_em->getUnitOfWork()->getEntityState($user), "State should be UnitOfWork::STATE_NEW");
        
        $this->_em->persist($user);
        
        $this->assertEquals(\Doctrine\ORM\UnitOfWork::STATE_MANAGED, $this->_em->getUnitOfWork()->getEntityState($user), "State should be UnitOfWork::STATE_MANAGED");
        
        $this->_em->remove($user);

        $this->assertEquals(\Doctrine\ORM\UnitOfWork::STATE_NEW, $this->_em->getUnitOfWork()->getEntityState($user), "State should be UnitOfWork::STATE_NEW");
        
        $this->_em->persist($user);
        $this->_em->flush();
        $id = $user->getId();
        
        $this->_em->remove($user);
        
        $this->assertEquals(\Doctrine\ORM\UnitOfWork::STATE_REMOVED, $this->_em->getUnitOfWork()->getEntityState($user), "State should be UnitOfWork::STATE_REMOVED");
        $this->_em->flush();
        
        $this->assertEquals(\Doctrine\ORM\UnitOfWork::STATE_NEW, $this->_em->getUnitOfWork()->getEntityState($user), "State should be UnitOfWork::STATE_NEW");
        
        $this->assertNull($this->_em->find('Doctrine\Tests\Models\CMS\CmsUser', $id));
    }
    
    public function testOneToManyOrphanRemoval()
    {
        $user = new CmsUser;
        $user->name = 'Guilherme';
        $user->username = 'gblanco';
        $user->status = 'developer';

        for ($i=0; $i<3; ++$i) {
            $phone = new CmsPhonenumber;
            $phone->phonenumber = 100 + $i;
            $user->addPhonenumber($phone);
        }

        $this->_em->persist($user);

        $this->_em->flush();

        $user->getPhonenumbers()->remove(0);
        $this->assertEquals(2, count($user->getPhonenumbers()));

        $this->_em->flush();

        // Check that there are just 2 phonenumbers left
        $count = $this->_em->getConnection()->fetchColumn("SELECT COUNT(*) FROM cms_phonenumbers");
        $this->assertEquals(2, $count); // only 2 remaining

        // check that clear() removes the others via orphan removal
        $user->getPhonenumbers()->clear();
        $this->_em->flush();
        $this->assertEquals(0, $this->_em->getConnection()->fetchColumn("select count(*) from cms_phonenumbers"));
    }

    public function testBasicQuery()
    {
        $user = new CmsUser;
        $user->name = 'Guilherme';
        $user->username = 'gblanco';
        $user->status = 'developer';
        $this->_em->persist($user);
        $this->_em->flush();

        $query = $this->_em->createQuery("select u from Doctrine\Tests\Models\CMS\CmsUser u");

        $users = $query->getResult();

        $this->assertEquals(1, count($users));
        $this->assertEquals('Guilherme', $users[0]->name);
        $this->assertEquals('gblanco', $users[0]->username);
        $this->assertEquals('developer', $users[0]->status);
        //$this->assertNull($users[0]->phonenumbers);
        //$this->assertNull($users[0]->articles);

        $usersArray = $query->getArrayResult();

        $this->assertTrue(is_array($usersArray));
        $this->assertEquals(1, count($usersArray));
        $this->assertEquals('Guilherme', $usersArray[0]['name']);
        $this->assertEquals('gblanco', $usersArray[0]['username']);
        $this->assertEquals('developer', $usersArray[0]['status']);

        $usersScalar = $query->getScalarResult();

        $this->assertTrue(is_array($usersScalar));
        $this->assertEquals(1, count($usersScalar));
        $this->assertEquals('Guilherme', $usersScalar[0]['u_name']);
        $this->assertEquals('gblanco', $usersScalar[0]['u_username']);
        $this->assertEquals('developer', $usersScalar[0]['u_status']);
    }

    public function testBasicOneToManyInnerJoin()
    {
        $user = new CmsUser;
        $user->name = 'Guilherme';
        $user->username = 'gblanco';
        $user->status = 'developer';
        $this->_em->persist($user);
        $this->_em->flush();

        $query = $this->_em->createQuery("select u from Doctrine\Tests\Models\CMS\CmsUser u join u.phonenumbers p");

        $users = $query->getResult();

        $this->assertEquals(0, count($users));
    }

    public function testBasicOneToManyLeftJoin()
    {
        $user = new CmsUser;
        $user->name = 'Guilherme';
        $user->username = 'gblanco';
        $user->status = 'developer';
        $this->_em->persist($user);
        $this->_em->flush();

        $query = $this->_em->createQuery("select u,p from Doctrine\Tests\Models\CMS\CmsUser u left join u.phonenumbers p");

        $users = $query->getResult();

        $this->assertEquals(1, count($users));
        $this->assertEquals('Guilherme', $users[0]->name);
        $this->assertEquals('gblanco', $users[0]->username);
        $this->assertEquals('developer', $users[0]->status);
        $this->assertTrue($users[0]->phonenumbers instanceof \Doctrine\ORM\PersistentCollection);
        $this->assertTrue($users[0]->phonenumbers->isInitialized());
        $this->assertEquals(0, $users[0]->phonenumbers->count());
        //$this->assertNull($users[0]->articles);
    }
    
    public function testBasicRefresh()
    {
        $user = new CmsUser;
        $user->name = 'Guilherme';
        $user->username = 'gblanco';
        $user->status = 'developer';

        $this->_em->persist($user);
        $this->_em->flush();
        
        $user->status = 'mascot';
        
        $this->assertEquals('mascot', $user->status);
        $this->_em->refresh($user);
        $this->assertEquals('developer', $user->status);
    }

    /**
     * @group DDC-833
     */
    public function testRefreshResetsCollection()
    {
        $user = new CmsUser;
        $user->name = 'Guilherme';
        $user->username = 'gblanco';
        $user->status = 'developer';

        // Add a phonenumber
        $ph1 = new CmsPhonenumber;
        $ph1->phonenumber = "12345";
        $user->addPhonenumber($ph1);

        // Add a phonenumber
        $ph2 = new CmsPhonenumber;
        $ph2->phonenumber = "54321";

        $this->_em->persist($user);
        $this->_em->persist($ph1);
        $this->_em->persist($ph2);
        $this->_em->flush();

        $user->addPhonenumber($ph2);

        $this->assertEquals(2, count($user->phonenumbers));
        $this->_em->refresh($user);

        $this->assertEquals(1, count($user->phonenumbers));
    }

    /**
     * @group DDC-833
     */
    public function testDqlRefreshResetsCollection()
    {
        $user = new CmsUser;
        $user->name = 'Guilherme';
        $user->username = 'gblanco';
        $user->status = 'developer';

        // Add a phonenumber
        $ph1 = new CmsPhonenumber;
        $ph1->phonenumber = "12345";
        $user->addPhonenumber($ph1);

        // Add a phonenumber
        $ph2 = new CmsPhonenumber;
        $ph2->phonenumber = "54321";

        $this->_em->persist($user);
        $this->_em->persist($ph1);
        $this->_em->persist($ph2);
        $this->_em->flush();

        $user->addPhonenumber($ph2);

        $this->assertEquals(2, count($user->phonenumbers));
        $dql = "SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE u.id = ?1";
        $user = $this->_em->createQuery($dql)
                          ->setParameter(1, $user->id)
                          ->setHint(Query::HINT_REFRESH, true)
                          ->getSingleResult();

        $this->assertEquals(1, count($user->phonenumbers));
    }

    /**
     * @group DDC-833
     */
    public function testCreateEntityOfProxy()
    {
        $user = new CmsUser;
        $user->name = 'Guilherme';
        $user->username = 'gblanco';
        $user->status = 'developer';

        // Add a phonenumber
        $ph1 = new CmsPhonenumber;
        $ph1->phonenumber = "12345";
        $user->addPhonenumber($ph1);

        // Add a phonenumber
        $ph2 = new CmsPhonenumber;
        $ph2->phonenumber = "54321";

        $this->_em->persist($user);
        $this->_em->persist($ph1);
        $this->_em->persist($ph2);
        $this->_em->flush();
        $this->_em->clear();

        $userId = $user->id;
        $user = $this->_em->getReference('Doctrine\Tests\Models\CMS\CmsUser', $user->id);
        
        $dql = "SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE u.id = ?1";
        $user = $this->_em->createQuery($dql)
                          ->setParameter(1, $userId)
                          ->getSingleResult();

        $this->assertEquals(1, count($user->phonenumbers));
    }

    public function testAddToCollectionDoesNotInitialize()
    {
        $user = new CmsUser;
        $user->name = 'Guilherme';
        $user->username = 'gblanco';
        $user->status = 'developer';

        for ($i=0; $i<3; ++$i) {
            $phone = new CmsPhonenumber;
            $phone->phonenumber = 100 + $i;
            $user->addPhonenumber($phone);
        }

        $this->_em->persist($user);
        $this->_em->flush();
        $this->_em->clear();
        
        $this->assertEquals(3, $user->getPhonenumbers()->count());
        
        $query = $this->_em->createQuery("select u from Doctrine\Tests\Models\CMS\CmsUser u where u.username='gblanco'");

        $gblanco = $query->getSingleResult();
        
        $this->assertFalse($gblanco->getPhonenumbers()->isInitialized());
        
        $newPhone = new CmsPhonenumber;
        $newPhone->phonenumber = 555;
        $gblanco->addPhonenumber($newPhone);
        
        $this->assertFalse($gblanco->getPhonenumbers()->isInitialized());
        $this->_em->persist($gblanco);

        $this->_em->flush();
        $this->_em->clear();
        
        $query = $this->_em->createQuery("select u, p from Doctrine\Tests\Models\CMS\CmsUser u join u.phonenumbers p where u.username='gblanco'");
        $gblanco2 = $query->getSingleResult();
        $this->assertEquals(4, $gblanco2->getPhonenumbers()->count());
    }
    
    public function testInitializeCollectionWithNewObjectsRetainsNewObjects()
    {
        $user = new CmsUser;
        $user->name = 'Guilherme';
        $user->username = 'gblanco';
        $user->status = 'developer';

        for ($i=0; $i<3; ++$i) {
            $phone = new CmsPhonenumber;
            $phone->phonenumber = 100 + $i;
            $user->addPhonenumber($phone);
        }

        $this->_em->persist($user);
        $this->_em->flush();
        $this->_em->clear();
        
        $this->assertEquals(3, $user->getPhonenumbers()->count());
        
        $query = $this->_em->createQuery("select u from Doctrine\Tests\Models\CMS\CmsUser u where u.username='gblanco'");

        $gblanco = $query->getSingleResult();
        
        $this->assertFalse($gblanco->getPhonenumbers()->isInitialized());
        
        $newPhone = new CmsPhonenumber;
        $newPhone->phonenumber = 555;
        $gblanco->addPhonenumber($newPhone);
        
        $this->assertFalse($gblanco->getPhonenumbers()->isInitialized());
        $this->assertEquals(4, $gblanco->getPhonenumbers()->count());
        $this->assertTrue($gblanco->getPhonenumbers()->isInitialized());

        $this->_em->flush();
        $this->_em->clear();
        
        $query = $this->_em->createQuery("select u, p from Doctrine\Tests\Models\CMS\CmsUser u join u.phonenumbers p where u.username='gblanco'");
        $gblanco2 = $query->getSingleResult();
        $this->assertEquals(4, $gblanco2->getPhonenumbers()->count());
    }
    
    public function testSetSetAssociationWithGetReference()
    {
        $user = new CmsUser;
        $user->name = 'Guilherme';
        $user->username = 'gblanco';
        $user->status = 'developer';        
        $this->_em->persist($user);
        
        $address = new CmsAddress;
        $address->country = 'Germany';
        $address->city = 'Berlin';
        $address->zip = '12345';
        $this->_em->persist($address);
        
        $this->_em->flush();
        $this->_em->detach($address);
        
        $this->assertFalse($this->_em->contains($address));
        $this->assertTrue($this->_em->contains($user));
        
        // Assume we only got the identifier of the address and now want to attach
        // that address to the user without actually loading it, using getReference().
        $addressRef = $this->_em->getReference('Doctrine\Tests\Models\CMS\CmsAddress', $address->getId());
        
        //$addressRef->getId();
        //\Doctrine\Common\Util\Debug::dump($addressRef);
        
        $user->setAddress($addressRef); // Ugh! Initializes address 'cause of $address->setUser($user)!
        
        $this->_em->flush();
        $this->_em->clear();
        
        // Check with a fresh load that the association is indeed there
        $query = $this->_em->createQuery("select u, a from Doctrine\Tests\Models\CMS\CmsUser u join u.address a where u.username='gblanco'");
        $gblanco = $query->getSingleResult();

        $this->assertTrue($gblanco instanceof CmsUser);
        $this->assertTrue($gblanco->getAddress() instanceof CmsAddress);
        $this->assertEquals('Berlin', $gblanco->getAddress()->getCity());
        
    }
    
    public function testOneToManyCascadeRemove()
    {
        $user = new CmsUser;
        $user->name = 'Guilherme';
        $user->username = 'gblanco';
        $user->status = 'developer';

        for ($i=0; $i<3; ++$i) {
            $phone = new CmsPhonenumber;
            $phone->phonenumber = 100 + $i;
            $user->addPhonenumber($phone);
        }

        $this->_em->persist($user);
        $this->_em->flush();
        $this->_em->clear();
        
        $query = $this->_em->createQuery("select u from Doctrine\Tests\Models\CMS\CmsUser u where u.username='gblanco'");
        $gblanco = $query->getSingleResult();
        
        $this->_em->remove($gblanco);
        $this->_em->flush();
        
        $this->_em->clear();
        
        $this->assertEquals(0, $this->_em->createQuery(
                "select count(p.phonenumber) from Doctrine\Tests\Models\CMS\CmsPhonenumber p")
                ->getSingleScalarResult());
        
        $this->assertEquals(0, $this->_em->createQuery(
                "select count(u.id) from Doctrine\Tests\Models\CMS\CmsUser u")
                ->getSingleScalarResult());
    }

    public function testTextColumnSaveAndRetrieve()
    {
        $user = new CmsUser;
        $user->name = 'Guilherme';
        $user->username = 'gblanco';
        $user->status = 'developer';

        $this->_em->persist($user);

        $article = new \Doctrine\Tests\Models\CMS\CmsArticle();
        $article->text = "Lorem ipsum dolor sunt.";
        $article->topic = "A Test Article!";
        $article->setAuthor($user);

        $this->_em->persist($article);
        $this->_em->flush();
        $articleId = $article->id;

        $this->_em->clear();

        // test find() with leading backslash at the same time
        $articleNew = $this->_em->find('\Doctrine\Tests\Models\CMS\CmsArticle', $articleId);
        $this->assertTrue($this->_em->contains($articleNew));
        $this->assertEquals("Lorem ipsum dolor sunt.", $articleNew->text);
        
        $this->assertNotSame($article, $articleNew);

        $articleNew->text = "Lorem ipsum dolor sunt. And stuff!";

        $this->_em->flush();
        $this->_em->clear();

        $articleNew = $this->_em->find('Doctrine\Tests\Models\CMS\CmsArticle', $articleId);
        $this->assertEquals("Lorem ipsum dolor sunt. And stuff!", $articleNew->text);
        $this->assertTrue($this->_em->contains($articleNew));
    }

    public function testFlushDoesNotIssueUnnecessaryUpdates()
    {
        $user = new CmsUser;
        $user->name = 'Guilherme';
        $user->username = 'gblanco';
        $user->status = 'developer';
        
        $address = new CmsAddress;
        $address->country = 'Germany';
        $address->city = 'Berlin';
        $address->zip = '12345';
        
        $address->user = $user;
        $user->address = $address;
        
        $article = new \Doctrine\Tests\Models\CMS\CmsArticle();
        $article->text = "Lorem ipsum dolor sunt.";
        $article->topic = "A Test Article!";
        $article->setAuthor($user);
        
        $this->_em->persist($article);
        $this->_em->persist($user);
        
        //$this->_em->getConnection()->getConfiguration()->setSQLLogger(new \Doctrine\DBAL\Logging\EchoSQLLogger);
        
        $this->_em->flush();
        $this->_em->clear();
        
        $query = $this->_em->createQuery('select u,a,ad from Doctrine\Tests\Models\CMS\CmsUser u join u.articles a join u.address ad');
        $user2 = $query->getSingleResult();

        $this->assertEquals(1, count($user2->articles));
        $this->assertTrue($user2->address instanceof CmsAddress);
        
        $oldLogger = $this->_em->getConnection()->getConfiguration()->getSQLLogger();
        $debugStack = new \Doctrine\DBAL\Logging\DebugStack;
        $this->_em->getConnection()->getConfiguration()->setSQLLogger($debugStack);
        
        $this->_em->flush();
        $this->assertEquals(0, count($debugStack->queries));
        
        $this->_em->getConnection()->getConfiguration()->setSQLLogger($oldLogger);
    }
    
    public function testRemoveEntityByReference()
    {
        $user = new CmsUser;
        $user->name = 'Guilherme';
        $user->username = 'gblanco';
        $user->status = 'developer';
        
        //$this->_em->getConnection()->getConfiguration()->setSQLLogger(new \Doctrine\DBAL\Logging\EchoSQLLogger);
        
        $this->_em->persist($user);
        $this->_em->flush();
        $this->_em->clear();
        
        $userRef = $this->_em->getReference('Doctrine\Tests\Models\CMS\CmsUser', $user->getId());
        $this->_em->remove($userRef);
        $this->_em->flush();
        $this->_em->clear();
        
        $this->assertEquals(0, $this->_em->getConnection()->fetchColumn("select count(*) from cms_users"));
        
        //$this->_em->getConnection()->getConfiguration()->setSQLLogger(null);
    }

    public function testQueryEntityByReference()
    {
        $user = new CmsUser;
        $user->name = 'Guilherme';
        $user->username = 'gblanco';
        $user->status = 'developer';
        
        $address = new CmsAddress;
        $address->country = 'Germany';
        $address->city = 'Berlin';
        $address->zip = '12345';
        
        $user->setAddress($address);

        $this->_em->transactional(function($em) use($user) {
            $em->persist($user);
        });
        $this->_em->clear();
        
        //$this->_em->getConnection()->getConfiguration()->setSQLLogger(new \Doctrine\DBAL\Logging\EchoSQLLogger);
        
        $userRef = $this->_em->getReference('Doctrine\Tests\Models\CMS\CmsUser', $user->getId());
        $address2 = $this->_em->createQuery('select a from Doctrine\Tests\Models\CMS\CmsAddress a where a.user = :user')
                ->setParameter('user', $userRef)
                ->getSingleResult();
        
        $this->assertTrue($address2->getUser() instanceof \Doctrine\ORM\Proxy\Proxy);
        $this->assertTrue($userRef === $address2->getUser());
        $this->assertFalse($userRef->__isInitialized__);
        $this->assertEquals('Germany', $address2->country);
        $this->assertEquals('Berlin', $address2->city);
        $this->assertEquals('12345', $address2->zip);
    }

    public function testOneToOneNullUpdate()
    {
        $user = new CmsUser();
        $user->username = "beberlei";
        $user->name = "Benjamin E.";
        $user->status = 'active';

        $address = new CmsAddress();
        $address->city = "Bonn";
        $address->zip = "12354";
        $address->country = "Germany";
        $address->street = "somestreet";
        $address->user = $user;

        $this->_em->persist($address);
        $this->_em->persist($user);
        $this->_em->flush();

        $this->assertEquals(1, $this->_em->getConnection()->fetchColumn("select 1 from cms_addresses where user_id = ".$user->id));

        $address->user = null;
        $this->_em->flush();

        $this->assertNotEquals(1, $this->_em->getConnection()->fetchColumn("select 1 from cms_addresses where user_id = ".$user->id));
    }

    /**
     * @group DDC-600
     * @group DDC-455
     */
    public function testNewAssociatedEntityDuringFlushThrowsException()
    {
        //$this->_em->getConnection()->getConfiguration()->setSQLLogger(new \Doctrine\DBAL\Logging\EchoSQLLogger);
        $user = new CmsUser();
        $user->username = "beberlei";
        $user->name = "Benjamin E.";
        $user->status = 'active';

        $address = new CmsAddress();
        $address->city = "Bonn";
        $address->zip = "12354";
        $address->country = "Germany";
        $address->street = "somestreet";
        $address->user = $user;

        $this->_em->persist($address);
        // pretend we forgot to persist $user
        try {
            $this->_em->flush(); // should raise an exception
            $this->fail();
        } catch (\InvalidArgumentException $expected) {}
    }

    /**
     * @group DDC-600
     * @group DDC-455
     */
    public function testNewAssociatedEntityDuringFlushThrowsException2()
    {
        //$this->_em->getConnection()->getConfiguration()->setSQLLogger(new \Doctrine\DBAL\Logging\EchoSQLLogger);
        $user = new CmsUser();
        $user->username = "beberlei";
        $user->name = "Benjamin E.";
        $user->status = 'active';

        $address = new CmsAddress();
        $address->city = "Bonn";
        $address->zip = "12354";
        $address->country = "Germany";
        $address->street = "somestreet";
        $address->user = $user;

        $this->_em->persist($address);
        $this->_em->persist($user);
        $this->_em->flush();

        $u2 = new CmsUser;
        $u2->username = "beberlei";
        $u2->name = "Benjamin E.";
        $u2->status = 'inactive';
        $address->user = $u2;
        // pretend we forgot to persist $u2
        try {
            $this->_em->flush(); // should raise an exception
            $this->fail();
        } catch (\InvalidArgumentException $expected) {}
    }

    /**
     * @group DDC-600
     * @group DDC-455
     */
    public function testNewAssociatedEntityDuringFlushThrowsException3()
    {
        //$this->_em->getConnection()->getConfiguration()->setSQLLogger(new \Doctrine\DBAL\Logging\EchoSQLLogger);
        $art = new CmsArticle();
        $art->topic = 'topic';
        $art->text = 'the text';

        $com = new CmsComment();
        $com->topic = 'Good';
        $com->text = 'Really good!';
        $art->addComment($com);
        
        $this->_em->persist($art);
        // pretend we forgot to persist $com
        try {
            $this->_em->flush(); // should raise an exception
            $this->fail();
        } catch (\InvalidArgumentException $expected) {}
    }

    public function testOneToOneOrphanRemoval()
    {
        //$this->_em->getConnection()->getConfiguration()->setSQLLogger(new \Doctrine\DBAL\Logging\EchoSQLLogger);
        $user = new CmsUser();
        $user->username = "beberlei";
        $user->name = "Benjamin E.";
        $user->status = 'active';

        $address = new CmsAddress();
        $address->city = "Bonn";
        $address->zip = "12354";
        $address->country = "Germany";
        $address->street = "somestreet";
        $address->user = $user;
        $user->address = $address;

        $this->_em->persist($address);
        $this->_em->persist($user);
        $this->_em->flush();
        $addressId = $address->getId();

        $user->address = null;

        $this->_em->flush();

        $this->assertEquals(0, $this->_em->getConnection()->fetchColumn("select count(*) from cms_addresses"));

        // check orphan removal through replacement
        $user->address = $address;
        $address->user = $user;

        $this->_em->flush();
        $this->assertEquals(1, $this->_em->getConnection()->fetchColumn("select count(*) from cms_addresses"));

        // remove $address to free up unique key id
        $this->_em->remove($address);
        $this->_em->flush();

        $newAddress = new CmsAddress();
        $newAddress->city = "NewBonn";
        $newAddress->zip = "12354";
        $newAddress->country = "NewGermany";
        $newAddress->street = "somenewstreet";
        $newAddress->user = $user;
        $user->address = $newAddress;

        $this->_em->flush();
        $this->assertEquals(1, $this->_em->getConnection()->fetchColumn("select count(*) from cms_addresses"));
        $this->assertEquals(0, $this->_em->getConnection()->fetchColumn("select count(*) from cms_addresses where id=".$addressId.""));
    }

    public function testGetPartialReferenceToUpdateObjectWithoutLoadingIt()
    {
        $user = new CmsUser();
        $user->username = "beberlei";
        $user->name = "Benjamin E.";
        $user->status = 'active';
        $this->_em->persist($user);
        $this->_em->flush();
        $userId = $user->id;
        $this->_em->clear();

        $user = $this->_em->getPartialReference('Doctrine\Tests\Models\CMS\CmsUser', $userId);
        $this->assertTrue($this->_em->contains($user));
        $this->assertNull($user->getName());
        $this->assertEquals($userId, $user->id);

        $user->name = 'Stephan';
        $this->_em->flush();
        $this->_em->clear();

        $this->assertEquals('Benjamin E.', $this->_em->find(get_class($user), $userId)->name);
    }

    public function testMergePersistsNewEntities()
    {
        $user = new CmsUser();
        $user->username = "beberlei";
        $user->name = "Benjamin E.";
        $user->status = 'active';

        $managedUser = $this->_em->merge($user);
        $this->assertEquals('beberlei', $managedUser->username);
        $this->assertEquals('Benjamin E.', $managedUser->name);
        $this->assertEquals('active', $managedUser->status);

        $this->assertTrue($user !== $managedUser);
        $this->assertTrue($this->_em->contains($managedUser));

        $this->_em->flush();
        $userId = $managedUser->id;
        $this->_em->clear();

        $user2 = $this->_em->find(get_class($managedUser), $userId);
        $this->assertTrue($user2 instanceof CmsUser);
    }

    public function testMergeThrowsExceptionIfEntityWithGeneratedIdentifierDoesNotExist()
    {
        $user = new CmsUser();
        $user->username = "beberlei";
        $user->name = "Benjamin E.";
        $user->status = 'active';
        $user->id = 42;
        try {
            $this->_em->merge($user);
            $this->fail();
        } catch (\Doctrine\ORM\EntityNotFoundException $enfe) {}
    }

    /**
     * @group DDC-634
     */
    public function testOneToOneMergeSetNull()
    {
        //$this->_em->getConnection()->getConfiguration()->setSQLLogger(new \Doctrine\DBAL\Logging\EchoSQLLogger);
        $user = new CmsUser();
        $user->username = "beberlei";
        $user->name = "Benjamin E.";
        $user->status = 'active';

        $ph = new CmsPhonenumber();
        $ph->phonenumber = "12345";
        $user->addPhonenumber($ph);

        $this->_em->persist($user);
        $this->_em->persist($ph);
        $this->_em->flush();

        $this->_em->clear();

        $ph->user = null;
        $managedPh = $this->_em->merge($ph);

        $this->_em->flush();
        $this->_em->clear();

        $this->assertNull($this->_em->find(get_class($ph), $ph->phonenumber)->getUser());
    }

    /**
     * @group DDC-952
     */
    public function testManyToOneFetchModeQuery()
    {
        $user = new CmsUser();
        $user->username = "beberlei";
        $user->name = "Benjamin E.";
        $user->status = 'active';

        $article = new CmsArticle();
        $article->topic = "foo";
        $article->text = "bar";
        $article->user = $user;

        $this->_em->persist($article);
        $this->_em->persist($user);
        $this->_em->flush();
        $this->_em->clear();

        $qc = $this->getCurrentQueryCount();
        $dql = "SELECT a FROM Doctrine\Tests\Models\CMS\CmsArticle a WHERE a.id = ?1";
        $article = $this->_em->createQuery($dql)
                             ->setParameter(1, $article->id)
                             ->setFetchMode('Doctrine\Tests\Models\CMS\CmsArticle', 'user', \Doctrine\ORM\Mapping\ClassMetadata::FETCH_EAGER)
                             ->getSingleResult();
        $this->assertInstanceOf('Doctrine\ORM\Proxy\Proxy', $article->user, "It IS a proxy, ...");
        $this->assertTrue($article->user->__isInitialized__, "...but its initialized!");
        $this->assertEquals($qc+2, $this->getCurrentQueryCount());
    }
}
