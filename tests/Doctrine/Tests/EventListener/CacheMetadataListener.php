<?php

namespace Doctrine\Tests\EventListener;

use Doctrine\Common\Persistence\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;

class CacheMetadataListener
{

    /**
     * Tracks which entities we have already forced caching enabled on. This is
     * important to avoid some potential infinite-recursion issues.
     *
     * Key is the name of the entity, payload is unimportant.
     *
     * @var array
     */
    protected $enabledItems = [];

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

        $this->enableCaching($metadata, $em);
    }

    /**
     * @param ClassMetadata $metadata
     *
     * @return bool
     */
    private function isVisited(ClassMetadata $metadata)
    {
        return isset($this->enabledItems[$metadata->getName()]);
    }

    /**
     * @param ClassMetadata $metadata
     */
    private function recordVisit(ClassMetadata $metadata)
    {
        $this->enabledItems[$metadata->getName()] = true;
    }

    /**
     * @param ClassMetadata $metadata
     * @param EntityManager $em
     */
    protected function enableCaching(ClassMetadata $metadata, EntityManager $em)
    {
        if ($this->isVisited($metadata)) {
            return; // Already handled in the past
        }

        $cache = [
            'usage' => ClassMetadata::CACHE_USAGE_NONSTRICT_READ_WRITE
        ];

        if ($metadata->isVersioned) {
            return;
        }

        $metadata->enableCache($cache);

        $this->recordVisit($metadata);

        // only enable association-caching when the target has already been
        // given caching settings
        foreach ($metadata->associationMappings as $mapping) {
            $targetMeta = $em->getClassMetadata($mapping['targetEntity']);
            $this->enableCaching($targetMeta, $em);

            if ($this->isVisited($targetMeta)) {
                $metadata->enableAssociationCache($mapping['fieldName'], $cache);
            }
        }
    }
}
