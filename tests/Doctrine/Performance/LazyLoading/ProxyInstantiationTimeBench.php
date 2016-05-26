<?php

namespace Doctrine\Performance\LazyLoading;

use Doctrine\Common\Proxy\AbstractProxyFactory;
use Doctrine\Performance\EntityManagerFactory;
use Doctrine\Tests\Models\CMS\CmsEmployee;
use Doctrine\Tests\Models\CMS\CmsUser;
use PhpBench\Benchmark\Metadata\Annotations\BeforeMethods;

/**
 * @BeforeMethods({"init"})
 */
final class ProxyInstantiationTimeBench
{
    /**
     * @var AbstractProxyFactory
     */
    private $proxyFactory;

    public function init()
    {
        $this->proxyFactory = EntityManagerFactory::getEntityManager([])->getProxyFactory();
    }

    public function benchCmsUserInstantiation()
    {
        for ($i = 0; $i < 100000; ++$i) {
            $this->proxyFactory->getProxy(CmsUser::class, ['id' => $i]);
        }
    }

    public function benchCmsEmployeeInstantiation()
    {
        for ($i = 0; $i < 100000; ++$i) {
            $this->proxyFactory->getProxy(CmsEmployee::class, ['id' => $i]);
        }
    }
}

