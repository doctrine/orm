<?php

declare(strict_types=1);

namespace Doctrine\Tests\EventListener;

use Doctrine\Common\Persistence\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\EntityManagerInterface;
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
        $em       = $event->getObjectManager();

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

        $region = strtolower(str_replace('\\', '_', $metadata->getRootClassName()));

        $metadata->setCache(new CacheMetadata(CacheUsage::NONSTRICT_READ_WRITE, $region));

        $this->recordVisit($metadata);

        // only enable association-caching when the target has already been
        // given caching settings
        foreach ($metadata->associationMappings as $association) {
            $targetMeta = $em->getClassMetadata($association->getTargetEntity());

            $this->enableCaching($targetMeta, $em);

            if ($this->isVisited($targetMeta)) {
                $association->setCache(
                    new CacheMetadata(
                        CacheUsage::NONSTRICT_READ_WRITE,
                        sprintf('%s__%s', $region, $association->getName())
                    )
                );
            }
        }
    }
}
