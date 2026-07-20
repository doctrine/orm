<?php

declare(strict_types=1);

namespace Doctrine\ORM\Encrypt\KMS;

use Doctrine\ORM\Encrypt\KMS\Exception\UnknownKeyProvider;

use function array_keys;

abstract class AbstractKeyProviderRegistry implements KeyProviderRegistry
{
    /** @param array<string, string> $keyProviders */
    public function __construct(
        private readonly array $keyProviders,
        private readonly string $defaultKeyProviderName,
    ) {
        if (! isset($this->keyProviders[$this->defaultKeyProviderName])) {
            throw UnknownKeyProvider::create($this->defaultKeyProviderName, $this->getKeyProviderNames());
        }
    }

    /**
     * Fetches/creates the given services.
     *
     * @param string $name The name of the service.
     */
    abstract protected function getService(string $name): KeyProvider;

    public function getDefaultKeyProviderName(): string
    {
        return $this->defaultKeyProviderName;
    }

    public function getKeyProvider(string|null $name = null): KeyProvider
    {
        $name ??= $this->defaultKeyProviderName;

        if (! isset($this->keyProviders[$name])) {
            throw UnknownKeyProvider::create($name, $this->getKeyProviderNames());
        }

        return $this->getService($this->keyProviders[$name]);
    }

    /**
     * {@inheritDoc}
     */
    public function getKeyProviders(): array
    {
        $keyProviders = [];

        foreach (array_keys($this->keyProviders) as $name) {
            $keyProviders[$name] = $this->getKeyProvider($name);
        }

        return $keyProviders;
    }

    /**
     * {@inheritDoc}
     */
    public function getKeyProviderNames(): array
    {
        return array_keys($this->keyProviders);
    }
}
