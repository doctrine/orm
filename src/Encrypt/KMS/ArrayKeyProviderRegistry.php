<?php

declare(strict_types=1);

namespace Doctrine\ORM\Encrypt\KMS;

use function array_combine;
use function array_key_first;
use function array_keys;

final class ArrayKeyProviderRegistry extends AbstractKeyProviderRegistry
{
    /**
     * @param non-empty-array<string, KeyProvider> $keyProviders
     * @param string|null                          $defaultName  defaults to the first key
     */
    public function __construct(
        private readonly array $keyProviders,
        string|null $defaultName = null,
    ) {
        parent::__construct(
            array_combine($keys = array_keys($keyProviders), $keys),
            $defaultName ?? array_key_first($keyProviders),
        );
    }

    protected function getService(string $name): KeyProvider
    {
        return $this->keyProviders[$name];
    }
}
