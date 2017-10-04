<?php

namespace Doctrine\Performance\Hydration;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Performance\EntityManagerFactory;
use Doctrine\Tests\Models\CMS;
use PhpBench\Benchmark\Metadata\Annotations\BeforeMethods;

/**
 * @BeforeMethods({"init"})
 */
final class SimpleInsertPerformanceBench
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var CMS\CmsUser[]
     */
    private $users;

    /**
     * @var string
     */
    private $tableName;

    public function init()
    {
        $this->entityManager = EntityManagerFactory::getEntityManager([
            CMS\CmsUser::class,
            CMS\CmsPhonenumber::class,
            CMS\CmsAddress::class,
            CMS\CmsEmail::class,
            CMS\CmsGroup::class,
            CMS\CmsTag::class,
            CMS\CmsArticle::class,
            CMS\CmsComment::class,
        ]);

        for ($i = 1; $i <= 10000; ++$i) {
            $user           = new CMS\CmsUser;
            $user->status   = 'user';
            $user->username = 'user' . $i;
            $user->name     = 'Mr.Smith-' . $i;

            $this->users[$i] = $user;
        }

        $this->tableName = $this->entityManager->getClassMetadata(CMS\CmsUser::class)->getTableName();
    }

    public function benchHydration()
    {
        // Yes, this is a lot of overhead, but I have no better solution other than
        // completely mocking out the DB, which would be silly (query impact is
        // necessarily part of our benchmarks)
        $this->entityManager->getConnection()->executeQuery('DELETE FROM ' . $this->tableName)->execute();

        foreach ($this->users as $key => $user) {
            $this->entityManager->persist($user);

            if (! ($key % 20)) {
                $this->entityManager->flush();
                $this->entityManager->clear();
            }
        }
    }
}
