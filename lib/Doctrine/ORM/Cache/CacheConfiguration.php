<?php

declare(strict_types=1);

namespace Doctrine\ORM\Cache;

use Doctrine\ORM\Cache\Logging\CacheLogger;

/**
 * Configuration container for second-level cache.
 */
class CacheConfiguration
{
    /**
     * @var \Doctrine\ORM\Cache\CacheFactory|null
     */
    private $cacheFactory;

    /**
     * @var \Doctrine\ORM\Cache\RegionsConfiguration|null
     */
    private $regionsConfig;

    /**
     * @var \Doctrine\ORM\Cache\Logging\CacheLogger|null
     */
    private $cacheLogger;

    /**
     * @var \Doctrine\ORM\Cache\QueryCacheValidator|null
     */
    private $queryValidator;

    /**
     * @return \Doctrine\ORM\Cache\CacheFactory|null
     */
    public function getCacheFactory()
    {
        return $this->cacheFactory;
    }

    public function setCacheFactory(CacheFactory $factory)
    {
        $this->cacheFactory = $factory;
    }

    /**
     * @return \Doctrine\ORM\Cache\Logging\CacheLogger|null
     */
    public function getCacheLogger()
    {
        return $this->cacheLogger;
    }

    public function setCacheLogger(CacheLogger $logger)
    {
        $this->cacheLogger = $logger;
    }

    /**
     * @return \Doctrine\ORM\Cache\RegionsConfiguration
     */
    public function getRegionsConfiguration()
    {
        if ($this->regionsConfig === null) {
            $this->regionsConfig = new RegionsConfiguration();
        }

        return $this->regionsConfig;
    }

    public function setRegionsConfiguration(RegionsConfiguration $regionsConfig)
    {
        $this->regionsConfig = $regionsConfig;
    }

    /**
     * @return \Doctrine\ORM\Cache\QueryCacheValidator
     */
    public function getQueryValidator()
    {
        if ($this->queryValidator === null) {
            $this->queryValidator = new TimestampQueryCacheValidator(
                $this->cacheFactory->getTimestampRegion()
            );
        }

        return $this->queryValidator;
    }

    public function setQueryValidator(QueryCacheValidator $validator)
    {
        $this->queryValidator = $validator;
    }
}
