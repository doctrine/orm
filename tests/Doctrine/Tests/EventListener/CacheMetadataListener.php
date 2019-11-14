<?php

declare(strict_types=1);

namespace Doctrine\Tests\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Mapping\AssociationMetadata;
use Doctrine\ORM\Mapping\CacheMetadata;
use Doctrine\ORM\Mapping\CacheUsage;
use Doctrine\ORM\Mapping\ClassMetadata;
use function sprintf;
use function str_replace;
use function strstr;
use function strtolower;

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

    public function loadClassMetadata(LoadClassMetadataEventArgs $event)
    {
        $metadata = $event->getClassMetadata();
        $em       = $event->getEntityManager();

        /** @var $metadata \Doctrine\ORM\Mapping\ClassMetadata */
        if (strstr($metadata->getClassName(), 'Doctrine\Tests\Models\Cache')) {
            return;
        }

        $this->enableCaching($metadata, $em);
    }

    /**
     * @return bool
     */
    private function isVisited(ClassMetadata $metadata)
    {
        return isset($this->enabledItems[$metadata->getClassName()]);
    }

    private function recordVisit(ClassMetadata $metadata)
    {
        $this->enabledItems[$metadata->getClassName()] = true;
    }

    protected function enableCaching(ClassMetadata $metadata, EntityManagerInterface $em)
    {
        if ($this->isVisited($metadata)) {
            return; // Already handled in the past
        }

        if ($metadata->isVersioned()) {
            return;
        }

        $region        = strtolower(str_replace('\\', '_', $metadata->getRootClassName()));
        $cacheMetadata = new CacheMetadata(CacheUsage::NONSTRICT_READ_WRITE, $region);

        $metadata->setCache($cacheMetadata);

        $this->recordVisit($metadata);

        // only enable association-caching when the target has already been
        // given caching settings
        foreach ($metadata->getPropertiesIterator() as $property) {
            if (! ($property instanceof AssociationMetadata)) {
                continue;
            }

            $targetMetadata = $em->getClassMetadata($property->getTargetEntity());

            $this->enableCaching($targetMetadata, $em);

            if ($this->isVisited($targetMetadata)) {
                $region        = sprintf('%s__%s', $region, $property->getName());
                $cacheMetadata =  new CacheMetadata(CacheUsage::NONSTRICT_READ_WRITE, $region);

                $property->setCache($cacheMetadata);
            }
        }
    }
}
