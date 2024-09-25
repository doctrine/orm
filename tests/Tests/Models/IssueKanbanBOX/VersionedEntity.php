<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\IssueKanbanBOX;

use Doctrine\ORM\EntityNotFoundException;
use Doctrine\Persistence\Proxy;
use ReflectionProperty;

abstract class VersionedEntity
{
    protected EntityVersion|null $version = null;

    abstract protected function createVersion(string $version): EntityVersion;

    public function setVersion(string $version): void
    {
        //$this->ensureJoinedEntityPropertyInitialized($this, 'version');

        if ($this->version === null) {
            $this->version = $this->createVersion($version);

            return;
        }

        $this->version->version = $version;
    }

    public function getVersion(): string|null
    {
        //$this->ensureJoinedEntityPropertyInitialized($this, 'version');

        return $this->version?->version;
    }

    private function ensureJoinedEntityPropertyInitialized(object $entity, string $propertyName): void
    {
        $reflection = new ReflectionProperty($entity, $propertyName);
        $value      = $reflection->getValue($entity);
        if (! ($value instanceof Proxy)) {
            return;
        }

        try {
            $value->__load();
        } catch (EntityNotFoundException) {
            $reflection->setValue($entity, null);
        }
    }
}
