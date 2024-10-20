<?php

declare(strict_types=1);

namespace Doctrine\Tests\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Mapping\ClassMetadata;

use function strstr;

class CacheMetadataListener
{
    /**
     * Tracks which entities we have already forced caching enabled on. This is
     * important to avoid some potential infinite-recursion issues.
     *
     * Key is the name of the entity, payload is unimportant.
     *
     * @var array<string, bool>
     */
    protected $enabledItems = [];

    public function loadClassMetadata(LoadClassMetadataEventArgs $event): void
    {
        $metadata = $event->getClassMetadata();
        $em       = $event->getObjectManager();

        if (strstr($metadata->name, 'Doctrine\Tests\Models\Cache')) {
            return;
        }

        $this->enableCaching($metadata, $em);
    }

    private function isVisited(ClassMetadata $metadata): bool
    {
        return isset($this->enabledItems[$metadata->getName()]);
    }

    private function recordVisit(ClassMetadata $metadata): void
    {
        $this->enabledItems[$metadata->getName()] = true;
    }

    protected function enableCaching(ClassMetadata $metadata, EntityManagerInterface $em): void
    {
        if ($this->isVisited($metadata)) {
            return; // Already handled in the past
        }

        $cache = [
            'usage' => ClassMetadata::CACHE_USAGE_NONSTRICT_READ_WRITE,
        ];

        if ($metadata->isVersioned) {
            return;
        }

        $metadata->enableCache($cache);

        $this->recordVisit($metadata);

        // only enable association-caching when the target has already been
        // given caching settings
        foreach ($metadata->associationMappings as $mapping) {
            $targetMeta = $em->getClassMetadata($mapping->targetEntity);
            $this->enableCaching($targetMeta, $em);

            if ($this->isVisited($targetMeta)) {
                $metadata->enableAssociationCache($mapping->fieldName, $cache);
            }
        }
    }
}
