<?php

namespace Doctrine\Performance\Hydration;

use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Performance\EntityManagerFactory;
use Doctrine\Tests\Models\CMS\CmsUser;

/**
 * @BeforeMethods({"init"})
 */
final class SimpleHydrationBench
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var ObjectRepository
     */
    private $repository;

    public function init()
    {
        $this->entityManager = EntityManagerFactory::getEntityManager([
            \Doctrine\Tests\Models\CMS\CmsUser::class,
            \Doctrine\Tests\Models\CMS\CmsPhonenumber::class,
            \Doctrine\Tests\Models\CMS\CmsAddress::class,
            \Doctrine\Tests\Models\CMS\CmsEmail::class,
            \Doctrine\Tests\Models\CMS\CmsGroup::class,
            \Doctrine\Tests\Models\CMS\CmsTag::class,
            \Doctrine\Tests\Models\CMS\CmsArticle::class,
            \Doctrine\Tests\Models\CMS\CmsComment::class,
        ]);

        for ($i = 2; $i < 10000; ++$i) {
            $user = new CmsUser();

            $user->status   = 'developer';
            $user->username = 'jwage' . $i;
            $user->name     = 'Jonathan';

            $this->entityManager->persist($user);
        }

        $this->entityManager->flush();
        $this->entityManager->clear();

        $this->repository = $this->entityManager->getRepository(CmsUser::class);
    }

    public function benchSimpleFindOperationHydration()
    {
        $this->repository->findAll();
    }
}
