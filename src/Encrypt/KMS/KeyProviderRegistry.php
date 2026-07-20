<?php

declare(strict_types=1);

namespace Doctrine\ORM\Encrypt\KMS;

use Doctrine\ORM\Encrypt\KMS\Exception\UnknownKeyProvider;

interface KeyProviderRegistry
{
    public function getDefaultKeyProviderName(): string;

    /**
     * @param string|null $name null resolves to the default key provider
     *
     * @throws UnknownKeyProvider when $name is not registered.
     */
    public function getKeyProvider(string|null $name = null): KeyProvider;

    /** @return array<string, KeyProvider> indexed by key provider name */
    public function getKeyProviders(): array;

    /** @return list<string> All registered KeyProvider provider name. */
    public function getKeyProviderNames(): array;
}
