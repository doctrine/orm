<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\DBAL\LockMode;
use Doctrine\DBAL\Platforms\SQLitePlatform;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\TransactionRequiredException;
use Doctrine\Persistence\Mapping\MappingException;
use Doctrine\Tests\Models\Company\CompanyManager;
use Doctrine\Tests\OrmFunctionalTestCase;
use Doctrine\Tests\TestUtil;
use PHPUnit\Framework\Attributes\Group;

use function assert;

class DDC933Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        $this->useModelSet('company');

        parent::setUp();
    }

    #[Group('DDC-933')]
    public function testLockCTIClass(): void
    {
        if ($this->_em->getConnection()->getDatabasePlatform() instanceof SQLitePlatform) {
            self::markTestSkipped('It should not run on in-memory databases');
        }

        $manager = new CompanyManager();
        $manager->setName('beberlei');
        $manager->setSalary(1234);
        $manager->setTitle('Vice President of This Test');
        $manager->setDepartment('Foo');

        $this->_em->persist($manager);
        $this->_em->flush();

        $this->_em->beginTransaction();
        $this->_em->lock($manager, LockMode::PESSIMISTIC_READ);
        $this->_em->rollback();

        // if lock hasn't been released we'd have an exception here
        $this->assertManagerCanBeUpdatedOnAnotherConnection($manager->getId(), 'Master of This Test');
    }

    /**
     * @throws MappingException
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws TransactionRequiredException
     */
    private function assertManagerCanBeUpdatedOnAnotherConnection(int $id, string $newName): void
    {
        $em = $this->getEntityManager(TestUtil::getConnection());

        $manager = $em->find(CompanyManager::class, $id);
        assert($manager instanceof CompanyManager);
        $manager->setName($newName);

        $em->flush();
        $em->clear();

        self::assertSame($newName, $em->find(CompanyManager::class, $id)->getName());
    }
}
