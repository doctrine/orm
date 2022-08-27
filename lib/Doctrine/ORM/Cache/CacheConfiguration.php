<?php

declare(strict_types=1);

namespace Doctrine\ORM\Cache;

use Doctrine\ORM\Cache\Logging\CacheLogger;

/**
 * Configuration container for second-level cache.
 */
class CacheConfiguration
{
    private CacheFactory|null $cacheFactory          = null;
    private RegionsConfiguration|null $regionsConfig = null;
    private CacheLogger|null $cacheLogger            = null;
    private QueryCacheValidator|null $queryValidator = null;

    public function getCacheFactory(): CacheFactory|null
    {
        return $this->cacheFactory;
    }

    public function setCacheFactory(CacheFactory $factory): void
    {
        $this->cacheFactory = $factory;
    }

    public function getCacheLogger(): CacheLogger|null
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
            $this->cacheFactory->getTimestampRegion(),
        );
    }

    public function setQueryValidator(QueryCacheValidator $validator): void
    {
        $this->queryValidator = $validator;
    }
}
