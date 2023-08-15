<?php

declare(strict_types=1);

namespace Doctrine\Performance\LazyLoading;

use Doctrine\ORM\Proxy\InternalProxy as Proxy;
use Doctrine\Performance\EntityManagerFactory;
use Doctrine\Performance\Mock\NonProxyLoadingEntityManager;
use Doctrine\Tests\Models\CMS\CmsEmployee;
use Doctrine\Tests\Models\CMS\CmsUser;

/** @BeforeMethods({"init"}) */
final class ProxyInitializationTimeBench
{
    /** @var Proxy[] */
    private array|null $cmsUsers = null;

    /** @var Proxy[] */
    private array|null $cmsEmployees = null;

    /** @var Proxy[] */
    private array|null $initializedUsers = null;

    /** @var Proxy[] */
    private array|null $initializedEmployees = null;

    public function init(): void
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

    public function benchCmsUserInitialization(): void
    {
        foreach ($this->cmsUsers as $proxy) {
            $proxy->__load();
        }
    }

    public function benchCmsEmployeeInitialization(): void
    {
        foreach ($this->cmsEmployees as $proxy) {
            $proxy->__load();
        }
    }

    public function benchInitializationOfAlreadyInitializedCmsUsers(): void
    {
        foreach ($this->initializedUsers as $proxy) {
            $proxy->__load();
        }
    }

    public function benchInitializationOfAlreadyInitializedCmsEmployees(): void
    {
        foreach ($this->initializedEmployees as $proxy) {
            $proxy->__load();
        }
    }
}
