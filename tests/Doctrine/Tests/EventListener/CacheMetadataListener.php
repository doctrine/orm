<?php

namespace Doctrine\Tests\EventListener;

use Doctrine\Common\Persistence\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Mapping\ClassMetadata;

class CacheMetadataListener
{
    /**
     * @param \Doctrine\Common\Persistence\Event\LoadClassMetadataEventArgs $event
     */
    public function loadClassMetadata(LoadClassMetadataEventArgs $event)
    {
        $metadata = $event->getClassMetadata();
        $cache    = array(
            'usage' => ClassMetadata::CACHE_USAGE_NONSTRICT_READ_WRITE
        );

        /* @var $metadata \Doctrine\ORM\Mapping\ClassMetadata */
        if (strstr($metadata->name, 'Doctrine\Tests\Models\Cache')) {
            return;
        }

        if ($metadata->isVersioned) {
            return;
        }

        $metadata->enableCache($cache);

        foreach ($metadata->associationMappings as $mapping) {
            $metadata->enableAssociationCache($mapping['fieldName'], $cache);
        }
    }
}