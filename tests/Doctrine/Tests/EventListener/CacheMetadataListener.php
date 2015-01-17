<?php

namespace Doctrine\Tests\EventListener;

use Doctrine\Common\Persistence\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Mapping\ClassMetadata;

class CacheMetadataListener
{
    private $loaded = array();
    /**
     * @param \Doctrine\Common\Persistence\Event\LoadClassMetadataEventArgs $event
     */
    public function loadClassMetadata(LoadClassMetadataEventArgs $event)
    {
        $metadata = $event->getClassMetadata();

        if (isset($this->loaded[$metadata->name])) {
            return;
        }

        $this->loaded[$metadata->name] = true;

        $cache    = array(
            'usage' => ClassMetadata::CACHE_USAGE_NONSTRICT_READ_WRITE
        );

        /** @var $metadata \Doctrine\ORM\Mapping\ClassMetadata */
        if (strstr($metadata->name, 'Doctrine\Tests\Models\Cache')) {
            return;
        }

        if ($metadata->isVersioned) {
            return;
        }

        $factory = $event->getObjectManager()->getMetadataFactory();

        $metadata->enableCache($cache);
        foreach ($metadata->associationMappings as $mapping) {

            $targetMetadata = $factory->getMetadataFor($mapping['targetEntity']);

            if (!$targetMetadata->isVersioned) {
                $metadata->enableAssociationCache($mapping['fieldName'], $cache);
            }
        }
    }
}
