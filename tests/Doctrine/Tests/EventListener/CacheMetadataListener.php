<?php

namespace Doctrine\Tests\EventListener;

use Doctrine\Common\Persistence\Event\LoadClassMetadataEventArgs;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;

class CacheMetadataListener
{

    /**
     * Tracks which entities we have already forced caching enabled on. This is
     * important to avoid some potential infinite-recursion issues.
     * @var array
     */
    protected $enabledItems = array();

    /**
     * @param \Doctrine\Common\Persistence\Event\LoadClassMetadataEventArgs $event
     */
    public function loadClassMetadata(LoadClassMetadataEventArgs $event)
    {
        $metadata = $event->getClassMetadata();
        $em = $event->getObjectManager();

        /** @var $metadata \Doctrine\ORM\Mapping\ClassMetadata */
        if (strstr($metadata->name, 'Doctrine\Tests\Models\Cache')) {
            return;
        }

        if( ! $em instanceof EntityManager){
            return;
        }

        $this->enableCaching($metadata, $em);
    }

    /**
     * @param ClassMetadata $metadata
     * @param EntityManager $em
     */
    protected function enableCaching(ClassMetadata $metadata, EntityManager $em){

        if(array_key_exists($metadata->getName(), $this->enabledItems)){
            return; // Already handled in the past
        }

        $cache    = array(
            'usage' => ClassMetadata::CACHE_USAGE_NONSTRICT_READ_WRITE
        );

        if ($metadata->isVersioned) {
            return;
        }

        $metadata->enableCache($cache);

        $this->enabledItems[$metadata->getName()] = $metadata;

        /*
         * Only enable association-caching when the target has already been
         * given caching settings
         */
        foreach ($metadata->associationMappings as $mapping) {

            $targetMeta = $em->getClassMetadata($mapping['targetEntity']);
            $this->enableCaching($targetMeta, $em);

            if(array_key_exists($targetMeta->getName(), $this->enabledItems)){
                $metadata->enableAssociationCache($mapping['fieldName'], $cache);
            }
        }
    }
}
