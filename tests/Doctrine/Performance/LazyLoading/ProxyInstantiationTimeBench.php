<?php

declare(strict_types=1);

namespace Doctrine\Performance\LazyLoading;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Proxy\Factory\ProxyFactory;
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
     * @var ProxyFactory
     */
    private $proxyFactory;

    /**
     * @var ClassMetadata
     */
    private $cmsUserMetadata;

    /**
     * @var ClassMetadata
     */
    private $cmsEmployeeMetadata;

    public function init() : void
    {
        $entityManager             = EntityManagerFactory::getEntityManager([]);
        $this->proxyFactory        = $entityManager->getProxyFactory();
        $this->cmsUserMetadata     = $entityManager->getClassMetadata(CmsUser::class);
        $this->cmsEmployeeMetadata = $entityManager->getClassMetadata(CmsEmployee::class);
    }

    public function benchCmsUserInstantiation() : void
    {
        for ($i = 0; $i < 100000; ++$i) {
            $this->proxyFactory->getProxy($this->cmsUserMetadata, ['id' => $i]);
        }
    }

    public function benchCmsEmployeeInstantiation() : void
    {
        for ($i = 0; $i < 100000; ++$i) {
            $this->proxyFactory->getProxy($this->cmsEmployeeMetadata, ['id' => $i]);
        }
    }
}
