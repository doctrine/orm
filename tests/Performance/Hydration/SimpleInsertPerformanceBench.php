<?php

declare(strict_types=1);

namespace Doctrine\Performance\Hydration;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Performance\EntityManagerFactory;
use Doctrine\Tests\Models\CMS;
use Doctrine\Tests\Models\CMS\CmsAddress;
use Doctrine\Tests\Models\CMS\CmsArticle;
use Doctrine\Tests\Models\CMS\CmsComment;
use Doctrine\Tests\Models\CMS\CmsEmail;
use Doctrine\Tests\Models\CMS\CmsGroup;
use Doctrine\Tests\Models\CMS\CmsPhonenumber;
use Doctrine\Tests\Models\CMS\CmsTag;
use Doctrine\Tests\Models\CMS\CmsUser;
use PhpBench\Benchmark\Metadata\Annotations\BeforeMethods;

/** @BeforeMethods({"init"}) */
final class SimpleInsertPerformanceBench
{
    private EntityManagerInterface|null $entityManager = null;

    /** @var CMS\CmsUser[] */
    private array|null $users = null;

    private string|null $tableName = null;

    public function init(): void
    {
        $this->entityManager = EntityManagerFactory::getEntityManager([
            CmsUser::class,
            CmsPhonenumber::class,
            CmsAddress::class,
            CmsEmail::class,
            CmsGroup::class,
            CmsTag::class,
            CmsArticle::class,
            CmsComment::class,
        ]);

        for ($i = 1; $i <= 10000; ++$i) {
            $user           = new CmsUser();
            $user->status   = 'user';
            $user->username = 'user' . $i;
            $user->name     = 'Mr.Smith-' . $i;

            $this->users[$i] = $user;
        }

        $this->tableName = $this->entityManager->getClassMetadata(CmsUser::class)->getTableName();
    }

    public function benchHydration(): void
    {
        // Yes, this is a lot of overhead, but I have no better solution other than
        // completely mocking out the DB, which would be silly (query impact is
        // necessarily part of our benchmarks)
        $this->entityManager->getConnection()->executeStatement('DELETE FROM ' . $this->tableName);

        foreach ($this->users as $key => $user) {
            $this->entityManager->persist($user);

            if (! $key % 20) {
                $this->entityManager->flush();
                $this->entityManager->clear();
            }
        }
    }
}
