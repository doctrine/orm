<?php

namespace Doctrine\Performance\Hydration;

use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Performance\EntityManagerFactory;
use Doctrine\Tests\Models\CMS;

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
            CMS\CmsUser::class,
            CMS\CmsPhonenumber::class,
            CMS\CmsAddress::class,
            CMS\CmsEmail::class,
            CMS\CmsGroup::class,
            CMS\CmsTag::class,
            CMS\CmsArticle::class,
            CMS\CmsComment::class,
        ]);

        for ($i = 2; $i < 10000; ++$i) {
            $user = new CMS\CmsUser();

            $user->status   = 'developer';
            $user->username = 'jwage' . $i;
            $user->name     = 'Jonathan';

            $this->entityManager->persist($user);
        }

        $this->entityManager->flush();
        $this->entityManager->clear();

        $this->repository = $this->entityManager->getRepository(CMS\CmsUser::class);
    }

    public function benchHydration()
    {
        $this->repository->findAll();
    }
}
