<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Tests\Models\CMS\CmsAddress;
use Doctrine\Tests\Models\CMS\CmsEmail;
use Doctrine\Tests\Models\CMS\CmsUser;
use Doctrine\Tests\OrmFunctionalTestCase;

use function count;

/**
 * Tests a bidirectional one-to-one association mapping with orphan removal.
 */
class OneToOneOrphanRemovalTest extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        $this->useModelSet('cms');

        parent::setUp();
    }

    /**
     * Unlink oneToOne mapped entity on the owning side making
     * the inverse side become orphaned
     */
    public function testOrphanRemovalWhenUnlinkingOwningSide(): void
    {
        $user           = new CmsUser();
        $user->status   = 'dev';
        $user->username = 'beberlei';
        $user->name     = 'Benjamin Eberlei';

        $email        = new CmsEmail();
        $email->email = 'beberlei@domain.com';

        $user->setEmail($email);

        $this->_em->persist($user);
        $this->_em->flush();

        $userId = $user->getId();

        $this->_em->clear();

        $user = $this->_em->find(CmsUser::class, $userId);

        $user->setEmail(null);

        $this->_em->persist($user);
        $this->_em->flush();
        $this->_em->clear();

        $this->assertNoEntitiesExistInDB(CmsEmail::class, 'CmsEmail should be removed by orphanRemoval');
    }

    /**
     * Unlink oneToOne mapped entity on the inverse side making
     * the owning side become orphaned
     */
    public function testOrphanRemovalWhenUnlinkingInverseSide(): void
    {
        $user           = new CmsUser();
        $user->status   = 'dev';
        $user->username = 'romanb';
        $user->name     = 'Roman B.';

        $address          = new CmsAddress();
        $address->country = 'de';
        $address->zip     = 1234;
        $address->city    = 'Berlin';

        $user->setAddress($address);

        $this->_em->persist($user);
        $this->_em->flush();

        $userId = $user->getId();
        $addressId = $address->getId();

        $this->_em->clear();

        $userReloaded = $this->_em->getRepository(CmsUser::class)->find($userId);
        $this->assertInstanceOf(CmsUser::class, $userReloaded);
        $this->assertEntityExistsInDB(CmsAddress::class, $addressId, 'CmsAddress should exist');

        $userReloaded->setAddress(null);
        $this->_em->flush();

        $this->assertEntityNotExistsInDB(CmsAddress::class, $addressId, 'CmsAddress should be removed by orphanRemoval');
    }

    public function testOrphanRemoval(): void
    {
        $user           = new CmsUser();
        $user->status   = 'dev';
        $user->username = 'romanb';
        $user->name     = 'Roman B.';

        $address          = new CmsAddress();
        $address->country = 'de';
        $address->zip     = 1234;
        $address->city    = 'Berlin';

        $user->setAddress($address);

        $this->_em->persist($user);
        $this->_em->flush();

        $userId = $user->getId();

        $this->_em->clear();

        $userProxy = $this->_em->getReference(CmsUser::class, $userId);

        $this->_em->remove($userProxy);
        $this->_em->flush();
        $this->_em->clear();

        $query  = $this->_em->createQuery('SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u');
        $result = $query->getResult();

        $this->assertEquals(0, count($result), 'CmsUser should be removed by EntityManager');

        $this->assertNoEntitiesExistInDB(CmsEmail::class, 'CmsAddress should be removed by orphanRemoval');
    }

    private function assertEntityExistsInDB(string $modelClass, int $id, string $message): void
    {
        $this->assertCount(1, $this->getArrayResultByModelClassAndId($modelClass, $id), $message);
    }

    private function assertEntityNotExistsInDB(string $modelClass, int $id, string $message): void
    {
        $this->assertCount(0, $this->getArrayResultByModelClassAndId($modelClass, $id), $message);
    }

    private function getArrayResultByModelClassAndId(string $modelClass, int $id): array
    {
        $query = $this->_em->createQuery(sprintf('SELECT entity FROM %s entity WHERE entity.id = :id', $modelClass))
            ->setParameter('id', $id);

        return $query->getArrayResult();
    }

    private function assertNoEntitiesExistInDB(string $modelClass, string $message): void
    {
        $query  = $this->_em->createQuery(sprintf('SELECT entity FROM %s entity', $modelClass));
        $result = $query->getResult();
        $this->assertEquals(0, count($result), $message);
    }
}
