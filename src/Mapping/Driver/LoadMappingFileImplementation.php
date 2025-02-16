<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping\Driver;

use Doctrine\Persistence\Mapping\StaticReflectionService;

use function class_exists;

if (! class_exists(StaticReflectionService::class)) {
    /** @internal */
    trait LoadMappingFileImplementation
    {
        /**
         * {@inheritDoc}
         */
        protected function loadMappingFile($file): array
        {
            return $this->doLoadMappingFile($file);
        }
    }
} else {
    /** @internal */
    trait LoadMappingFileImplementation
    {
        /**
         * {@inheritDoc}
         */
        protected function loadMappingFile($file)
        {
            return $this->doLoadMappingFile($file);
        }
    }
}
