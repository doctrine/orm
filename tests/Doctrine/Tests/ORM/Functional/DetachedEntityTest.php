<?php

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Tests\Models\CMS\CmsUser;
use Doctrine\Tests\Models\CMS\CmsPhonenumber;
use Doctrine\Tests\Models\CMS\CmsAddress;
use Doctrine\Tests\Models\CMS\CmsArticle;
use Doctrine\ORM\UnitOfWork;

require_once __DIR__ . '/../../TestInit.php';

/**
 * Description of DetachedEntityTest
 *
 * @author robo
 */
class DetachedEntityTest extends \Doctrine\Tests\OrmFunctionalTestCase
{
    protected function setUp() {
        $this->useModelSet('cms');
        parent::setUp();
    }

    public function testSimpleDetachMerge() {
        $user = new CmsUser;
        $user->name = 'Roman';
        $user->username = 'romanb';
        $user->status = 'dev';
        $this->_em->persist($user);
        $this->_em->flush();
        $this->_em->clear();

        // $user is now detached

        $this->assertFalse($this->_em->contains($user));

        $user->name = 'Roman B.';

        //$this->assertEquals(UnitOfWork::STATE_DETACHED, $this->_em->getUnitOfWork()->getEntityState($user));

        $user2 = $this->_em->merge($user);

        $this->assertFalse($user === $user2);
        $this->assertTrue($this->_em->contains($user2));
        $this->assertEquals('Roman B.', $user2->name);
    }

    public function testSerializeUnserializeModifyMerge()
    {
        //$this->_em->getConnection()->getConfiguration()->setSQLLogger(new \Doctrine\DBAL\Logging\EchoSQLLogger);
        $user = new CmsUser;
        $user->name = 'Guilherme';
        $user->username = 'gblanco';
        $user->status = 'developer';

        $ph1 = new CmsPhonenumber;
        $ph1->phonenumber = "1234";
        $user->addPhonenumber($ph1);

        $this->_em->persist($user);
        $this->_em->flush();
        $this->assertTrue($this->_em->contains($user));
        $this->assertTrue($user->phonenumbers->isInitialized());

        $serialized = serialize($user);
        $this->_em->clear();
        $this->assertFalse($this->_em->contains($user));
        unset($user);

        $user = unserialize($serialized);

        $this->assertEquals(1, count($user->getPhonenumbers()), "Pre-Condition: 1 Phonenumber");

        $ph2 = new CmsPhonenumber;
        $ph2->phonenumber = "56789";
        $user->addPhonenumber($ph2);
        $oldPhonenumbers = $user->getPhonenumbers();
        $this->assertEquals(2, count($oldPhonenumbers), "Pre-Condition: 2 Phonenumbers");
        $this->assertFalse($this->_em->contains($user));

        $this->_em->persist($ph2);

        // Merge back in
        $user = $this->_em->merge($user); // merge cascaded to phonenumbers
        $this->assertInstanceOf('Doctrine\Tests\Models\CMS\CmsUser', $user->phonenumbers[0]->user);
        $this->assertInstanceOf('Doctrine\Tests\Models\CMS\CmsUser', $user->phonenumbers[1]->user);
        $im = $this->_em->getUnitOfWork()->getIdentityMap();
        $this->_em->flush();

        $this->assertTrue($this->_em->contains($user), "Failed to assert that merged user is contained inside EntityManager persistence context.");
        $phonenumbers = $user->getPhonenumbers();
        $this->assertNotSame($oldPhonenumbers, $phonenumbers, "Merge should replace the Detached Collection with a new PersistentCollection.");
        $this->assertEquals(2, count($phonenumbers), "Failed to assert that two phonenumbers are contained in the merged users phonenumber collection.");

        $this->assertInstanceOf('Doctrine\Tests\Models\CMS\CmsPhonenumber', $phonenumbers[1]);
        $this->assertTrue($this->_em->contains($phonenumbers[1]), "Failed to assert that second phonenumber in collection is contained inside EntityManager persistence context.");

        $this->assertInstanceOf('Doctrine\Tests\Models\CMS\CmsPhonenumber', $phonenumbers[0]);
        $this->assertTrue($this->_em->getUnitOfWork()->isInIdentityMap($phonenumbers[0]));
        $this->assertTrue($this->_em->contains($phonenumbers[0]), "Failed to assert that first phonenumber in collection is contained inside EntityManager persistence context.");
    }

    /**
     * @group DDC-203
     */
    public function testDetachedEntityThrowsExceptionOnFlush()
    {
        $ph = new CmsPhonenumber();
        $ph->phonenumber = '12345';
        $this->_em->persist($ph);
        $this->_em->flush();
        $this->_em->clear();
        $this->_em->persist($ph);
        try {
            $this->_em->flush();
            $this->fail();
        } catch (\Exception $expected) {}
    }

    public function testUninitializedLazyAssociationsAreIgnoredOnMerge()
    {
        $user = new CmsUser;
        $user->name = 'Guilherme';
        $user->username = 'gblanco';
        $user->status = 'developer';

        $address = new CmsAddress;
        $address->city = 'Berlin';
        $address->country = 'Germany';
        $address->street = 'Sesamestreet';
        $address->zip = 12345;
        $address->setUser($user);
        $this->_em->persist($address);
        $this->_em->persist($user);

        $this->_em->flush();
        $this->_em->clear();

        $address2 = $this->_em->find(get_class($address), $address->id);
        $this->assertInstanceOf('Doctrine\ORM\Proxy\Proxy', $address2->user);
        $this->assertFalse($address2->user->__isInitialized__);
        $detachedAddress2 = unserialize(serialize($address2));
        $this->assertInstanceOf('Doctrine\ORM\Proxy\Proxy', $detachedAddress2->user);
        $this->assertFalse($detachedAddress2->user->__isInitialized__);

        $managedAddress2 = $this->_em->merge($detachedAddress2);
        $this->assertInstanceOf('Doctrine\ORM\Proxy\Proxy', $managedAddress2->user);
        $this->assertFalse($managedAddress2->user === $detachedAddress2->user);
        $this->assertFalse($managedAddress2->user->__isInitialized__);
    }

    /**
     * @group DDC-822
     */
    public function testUseDetachedEntityAsQueryParameter()
    {
        $user = new CmsUser;
        $user->name = 'Guilherme';
        $user->username = 'gblanco';
        $user->status = 'developer';

        $this->_em->persist($user);

        $this->_em->flush();
        $this->_em->detach($user);

        $dql = "SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE u.id = ?1";
        $query = $this->_em->createQuery($dql);
        $query->setParameter(1, $user);

        $newUser = $query->getSingleResult();

        $this->assertInstanceOf('Doctrine\Tests\Models\CMS\CmsUser', $newUser);
        $this->assertEquals('gblanco', $newUser->username);
    }

    /**
     * @group DDC-920
     */
    public function testDetachManagedUnpersistedEntity()
    {
        $user = new CmsUser;
        $user->name = 'Guilherme';
        $user->username = 'gblanco';
        $user->status = 'developer';

        $this->_em->persist($user);
        $this->_em->detach($user);

        $this->_em->flush();

        $this->assertFalse($this->_em->contains($user));
        $this->assertFalse($this->_em->getUnitOfWork()->isInIdentityMap($user));
    }

    /**
     * @group DDC-1340
     */
    public function testMergeArticleWrongVersion()
    {
        $article = new CmsArticle();
        $article->topic = "test";
        $article->text = "test";

        $this->_em->persist($article);
        $this->_em->flush();

        $this->_em->detach($article);

        $sql = "UPDATE cms_articles SET version = version+1 WHERE id = " . $article->id;
        $this->_em->getConnection()->executeUpdate($sql);

        $this->setExpectedException('Doctrine\ORM\OptimisticLockException', 'The optimistic lock failed, version 1 was expected, but is actually 2');
        $this->_em->merge($article);
    }
}

