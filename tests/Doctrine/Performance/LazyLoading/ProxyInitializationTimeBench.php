<?php

declare(strict_types=1);

namespace Doctrine\Performance\LazyLoading;

use Doctrine\Performance\EntityManagerFactory;
use Doctrine\Performance\Mock\NonProxyLoadingEntityManager;
use Doctrine\Tests\Models\CMS\CmsEmployee;
use Doctrine\Tests\Models\CMS\CmsUser;
use PhpBench\Benchmark\Metadata\Annotations\BeforeMethods;
use ProxyManager\Proxy\GhostObjectInterface;

/**
 * @BeforeMethods({"init"})
 */
final class ProxyInitializationTimeBench
{
    /** @var GhostObjectInterface[] */
    private $cmsUsers;

    /** @var GhostObjectInterface[] */
    private $cmsEmployees;

    /** @var GhostObjectInterface[] */
    private $initializedUsers;

    /** @var GhostObjectInterface[] */
    private $initializedEmployees;

    public function init() : void
    {
        $entityManager = EntityManagerFactory::getEntityManager([]);
        $proxyFactory  = (new NonProxyLoadingEntityManager($entityManager))
            ->getProxyFactory();

        $cmsUser     = $entityManager->getClassMetadata(CmsUser::class);
        $cmsEmployee = $entityManager->getClassMetadata(CmsEmployee::class);

        for ($i = 0; $i < 10000; ++$i) {
            $this->cmsUsers[$i]             = $proxyFactory->getProxy($cmsUser, ['id' => $i]);
            $this->cmsEmployees[$i]         = $proxyFactory->getProxy($cmsEmployee, ['id' => $i]);
            $this->initializedUsers[$i]     = $proxyFactory->getProxy($cmsUser, ['id' => $i]);
            $this->initializedEmployees[$i] = $proxyFactory->getProxy($cmsEmployee, ['id' => $i]);

            $this->initializedUsers[$i]->initializeProxy();
            $this->initializedEmployees[$i]->initializeProxy();
        }
    }

    public function benchCmsUserInitialization() : void
    {
        foreach ($this->cmsUsers as $proxy) {
            $proxy->initializeProxy();
        }
    }

    public function benchCmsEmployeeInitialization() : void
    {
        foreach ($this->cmsEmployees as $proxy) {
            $proxy->initializeProxy();
        }
    }

    public function benchInitializationOfAlreadyInitializedCmsUsers() : void
    {
        foreach ($this->initializedUsers as $proxy) {
            $proxy->initializeProxy();
        }
    }

    public function benchInitializationOfAlreadyInitializedCmsEmployees() : void
    {
        foreach ($this->initializedEmployees as $proxy) {
            $proxy->initializeProxy();
        }
    }
}
