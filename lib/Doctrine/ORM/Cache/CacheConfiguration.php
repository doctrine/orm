<?php

declare(strict_types=1);

namespace Doctrine\ORM\Cache;

use Doctrine\ORM\Cache\Logging\CacheLogger;

/**
 * Configuration container for second-level cache.
 */
class CacheConfiguration
{
    private ?CacheFactory $cacheFactory          = null;
    private ?RegionsConfiguration $regionsConfig = null;
    private ?CacheLogger $cacheLogger            = null;
    private ?QueryCacheValidator $queryValidator = null;

    public function getCacheFactory(): ?CacheFactory
    {
        return $this->cacheFactory;
    }

    public function setCacheFactory(CacheFactory $factory): void
    {
        $this->cacheFactory = $factory;
    }

    public function getCacheLogger(): ?CacheLogger
    {
         return $this->cacheLogger;
    }

    public function setCacheLogger(CacheLogger $logger): void
    {
        $this->cacheLogger = $logger;
    }

    public function getRegionsConfiguration(): RegionsConfiguration
    {
        return $this->regionsConfig ??= new RegionsConfiguration();
    }

    public function setRegionsConfiguration(RegionsConfiguration $regionsConfig): void
    {
        $this->regionsConfig = $regionsConfig;
    }

    public function getQueryValidator(): QueryCacheValidator
    {
        return $this->queryValidator ??= new TimestampQueryCacheValidator(
            $this->cacheFactory->getTimestampRegion()
        );
    }

    public function setQueryValidator(QueryCacheValidator $validator): void
    {
        $this->queryValidator = $validator;
    }
}
