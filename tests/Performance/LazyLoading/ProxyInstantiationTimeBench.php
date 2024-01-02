<?php

declare(strict_types=1);

namespace Doctrine\Performance\LazyLoading;

use Doctrine\ORM\Proxy\ProxyFactory;
use Doctrine\Performance\EntityManagerFactory;
use Doctrine\Tests\Models\CMS\CmsEmployee;
use Doctrine\Tests\Models\CMS\CmsUser;
use PhpBench\Benchmark\Metadata\Annotations\BeforeMethods;

/** @BeforeMethods({"init"}) */
final class ProxyInstantiationTimeBench
{
    /** @var ProxyFactory */
    private $proxyFactory;

    public function init(): void
    {
        $this->proxyFactory = EntityManagerFactory::getEntityManager([])->getProxyFactory();
    }

    public function benchCmsUserInstantiation(): void
    {
        for ($i = 0; $i < 100000; ++$i) {
            $this->proxyFactory->getProxy(CmsUser::class, ['id' => $i]);
        }
    }

    public function benchCmsEmployeeInstantiation(): void
    {
        for ($i = 0; $i < 100000; ++$i) {
            $this->proxyFactory->getProxy(CmsEmployee::class, ['id' => $i]);
        }
    }
}
