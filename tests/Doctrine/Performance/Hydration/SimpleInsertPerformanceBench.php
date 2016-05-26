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
    }

    public function benchHydration()
    {
        foreach ($this->users as $key => $user) {
            $this->entityManager->persist($user);

            if (! ($key % 20)) {
                $this->entityManager->flush();
                $this->entityManager->clear();
            }
        }
    }
}
