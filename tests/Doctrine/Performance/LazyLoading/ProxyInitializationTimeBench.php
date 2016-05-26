<?php

namespace Doctrine\Performance\LazyLoading;

use Doctrine\ORM\Proxy\Proxy;
use Doctrine\Performance\EntityManagerFactory;
use Doctrine\Performance\Mock\NonProxyLoadingEntityManager;
use Doctrine\Tests\Models\CMS\CmsEmployee;
use Doctrine\Tests\Models\CMS\CmsUser;
use PhpBench\Benchmark\Metadata\Annotations\BeforeMethods;

/**
 * @BeforeMethods({"init"})
 */
final class ProxyInitializationTimeBench
{
    /**
     * @var Proxy[]
     */
    private $cmsUsers;

    /**
     * @var Proxy[]
     */
    private $cmsEmployees;

    /**
     * @var Proxy[]
     */
    private $initializedUsers;

    /**
     * @var Proxy[]
     */
    private $initializedEmployees;

    public function init()
    {
        $proxyFactory = (new NonProxyLoadingEntityManager(EntityManagerFactory::getEntityManager([])))
            ->getProxyFactory();

        for ($i = 0; $i < 10000; ++$i) {
            $this->cmsUsers[$i]             = $proxyFactory->getProxy(CmsUser::class, ['id' => $i]);
            $this->cmsEmployees[$i]         = $proxyFactory->getProxy(CmsEmployee::class, ['id' => $i]);
            $this->initializedUsers[$i]     = $proxyFactory->getProxy(CmsUser::class, ['id' => $i]);
            $this->initializedEmployees[$i] = $proxyFactory->getProxy(CmsEmployee::class, ['id' => $i]);

            $this->initializedUsers[$i]->__load();
            $this->initializedEmployees[$i]->__load();
        }
    }

    public function benchCmsUserInitialization()
    {
        foreach ($this->cmsUsers as $proxy) {
            $proxy->__load();
        }
    }

    public function benchCmsEmployeeInitialization()
    {
        foreach ($this->cmsEmployees as $proxy) {
            $proxy->__load();
        }
    }

    public function benchInitializationOfAlreadyInitializedCmsUsers()
    {
        foreach ($this->initializedUsers as $proxy) {
            $proxy->__load();
        }
    }

    public function benchInitializationOfAlreadyInitializedCmsEmployees()
    {
        foreach ($this->initializedEmployees as $proxy) {
            $proxy->__load();
        }
    }
}

