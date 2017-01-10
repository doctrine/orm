<?php

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\Proxy\Proxy;
use Doctrine\Tests\Models\CMS\CmsUser;
use Doctrine\Tests\Models\CMS\CmsPhonenumber;
use Doctrine\Tests\Models\CMS\CmsAddress;
use Doctrine\Tests\Models\CMS\CmsArticle;
use Doctrine\Tests\OrmFunctionalTestCase;

/**
 * Description of DetachedEntityTest
 *
 * @author robo
 */
class DetachedEntityTest extends OrmFunctionalTestCase
{
    protected function setUp()
    {
        $this->useModelSet('cms');
        parent::setUp();
    }

    public function testSimpleDetachMerge()
    {
        $user = new CmsUser;
        $user->name = 'Roman';
        $user->username = 'romanb';
        $user->status = 'dev';
        $this->em->persist($user);
        $this->em->flush();
        $this->em->clear();

        // $user is now detached
        self::assertFalse($this->em->contains($user));

        $user->name = 'Roman B.';

        $user2 = $this->em->merge($user);

        self::assertFalse($user === $user2);
        self::assertTrue($this->em->contains($user2));
        self::assertEquals('Roman B.', $user2->name);
    }

    public function testSerializeUnserializeModifyMerge()
    {
        $user = new CmsUser;
        $user->name = 'Guilherme';
        $user->username = 'gblanco';
        $user->status = 'developer';

        $ph1 = new CmsPhonenumber;
        $ph1->phonenumber = "1234";
        $user->addPhonenumber($ph1);

        $this->em->persist($user);
        $this->em->flush();

        self::assertTrue($this->em->contains($user));
        self::assertTrue($user->phonenumbers->isInitialized());

        $serialized = serialize($user);

        $this->em->clear();

        self::assertFalse($this->em->contains($user));

        unset($user);

        $user = unserialize($serialized);

        self::assertEquals(1, count($user->getPhonenumbers()), "Pre-Condition: 1 Phonenumber");

        $ph2 = new CmsPhonenumber;

        $ph2->phonenumber = "56789";
        $user->addPhonenumber($ph2);

        $oldPhonenumbers = $user->getPhonenumbers();

        self::assertEquals(2, count($oldPhonenumbers), "Pre-Condition: 2 Phonenumbers");
        self::assertFalse($this->em->contains($user));

        $this->em->persist($ph2);

        // Merge back in
        $user = $this->em->merge($user); // merge cascaded to phonenumbers
        self::assertInstanceOf(CmsUser::class, $user->phonenumbers[0]->user);
        self::assertInstanceOf(CmsUser::class, $user->phonenumbers[1]->user);
        $im = $this->em->getUnitOfWork()->getIdentityMap();
        $this->em->flush();

        self::assertTrue($this->em->contains($user), "Failed to assert that merged user is contained inside EntityManager persistence context.");
        $phonenumbers = $user->getPhonenumbers();
        self::assertNotSame($oldPhonenumbers, $phonenumbers, "Merge should replace the Detached Collection with a new PersistentCollection.");
        self::assertEquals(2, count($phonenumbers), "Failed to assert that two phonenumbers are contained in the merged users phonenumber collection.");

        self::assertInstanceOf(CmsPhonenumber::class, $phonenumbers[1]);
        self::assertTrue($this->em->contains($phonenumbers[1]), "Failed to assert that second phonenumber in collection is contained inside EntityManager persistence context.");

        self::assertInstanceOf(CmsPhonenumber::class, $phonenumbers[0]);
        self::assertTrue($this->em->getUnitOfWork()->isInIdentityMap($phonenumbers[0]));
        self::assertTrue($this->em->contains($phonenumbers[0]), "Failed to assert that first phonenumber in collection is contained inside EntityManager persistence context.");
    }

    /**
     * @group DDC-203
     */
    public function testDetachedEntityThrowsExceptionOnFlush()
    {
        $ph = new CmsPhonenumber();
        $ph->phonenumber = '12345';

        $this->em->persist($ph);
        $this->em->flush();
        $this->em->clear();

        $this->em->persist($ph);

        // since it tries to insert the object twice (with the same PK)
        $this->expectException(UniqueConstraintViolationException::class);

        $this->em->flush();
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
        $this->em->persist($address);
        $this->em->persist($user);

        $this->em->flush();
        $this->em->clear();

        $address2 = $this->em->find(get_class($address), $address->id);
        self::assertInstanceOf(Proxy::class, $address2->user);
        self::assertFalse($address2->user->__isInitialized__);
        $detachedAddress2 = unserialize(serialize($address2));
        self::assertInstanceOf(Proxy::class, $detachedAddress2->user);
        self::assertFalse($detachedAddress2->user->__isInitialized__);

        $managedAddress2 = $this->em->merge($detachedAddress2);
        self::assertInstanceOf(Proxy::class, $managedAddress2->user);
        self::assertFalse($managedAddress2->user === $detachedAddress2->user);
        self::assertFalse($managedAddress2->user->__isInitialized__);
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

        $this->em->persist($user);

        $this->em->flush();
        $this->em->detach($user);

        $dql = 'SELECT u FROM ' . CmsUser::class . ' u WHERE u.id = ?1';
        $query = $this->em->createQuery($dql);

        $query->setParameter(1, $user);

        $newUser = $query->getSingleResult();

        self::assertInstanceOf(CmsUser::class, $newUser);
        self::assertEquals('gblanco', $newUser->username);
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

        $this->em->persist($user);
        $this->em->detach($user);

        $this->em->flush();

        self::assertFalse($this->em->contains($user));
        self::assertFalse($this->em->getUnitOfWork()->isInIdentityMap($user));
    }

    /**
     * @group DDC-1340
     */
    public function testMergeArticleWrongVersion()
    {
        $article = new CmsArticle();
        $article->topic = "test";
        $article->text = "test";

        $this->em->persist($article);
        $this->em->flush();

        $this->em->detach($article);

        $sql = 'UPDATE cms_articles SET version = version + 1 WHERE id = ' . $article->id;
        $this->em->getConnection()->executeUpdate($sql);

        $this->expectException(OptimisticLockException::class);
        $this->expectExceptionMessage('The optimistic lock failed, version 1 was expected, but is actually 2');

        $this->em->merge($article);
    }
}

