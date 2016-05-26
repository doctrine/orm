<?php

namespace Doctrine\Performance\Hydration;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Doctrine\Performance\EntityManagerFactory;
use Doctrine\Tests\Mocks\HydratorMockStatement;
use Doctrine\Tests\Models\CMS;
use PhpBench\Benchmark\Metadata\Annotations\BeforeMethods;

/**
 * @BeforeMethods({"init"})
 */
final class SimpleInsertPerformanceTest
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

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
    }

    public function benchHydration()
    {
        $batchSize = 20;

        for ($i = 1; $i <= 10000; ++$i) {
            $user           = new CMS\CmsUser;
            $user->status   = 'user';
            $user->username = 'user' . $i;
            $user->name     = 'Mr.Smith-' . $i;

            $this->entityManager->persist($user);

            if (! ($i % $batchSize)) {
                $this->entityManager->flush();
                $this->entityManager->clear();
            }
        }
    }
}
