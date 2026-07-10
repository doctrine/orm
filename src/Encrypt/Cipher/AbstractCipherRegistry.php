<?php

declare(strict_types=1);

namespace Doctrine\ORM\Encrypt\Cipher;

use Doctrine\ORM\Encrypt\Cipher\Exception\UnknownCipher;

use function array_keys;

abstract class AbstractCipherRegistry implements CipherRegistry
{
    /** @param array<string, string> $cipherServices cipher name => service id */
    public function __construct(
        private readonly array $cipherServices,
        private readonly string $defaultCipherName,
    ) {
        if (! isset($this->cipherServices[$this->defaultCipherName])) {
            throw UnknownCipher::create($this->defaultCipherName, $this->getCipherNames());
        }
    }

    /**
     * Fetches/creates the given Cipher service.
     *
     * @param string $name The name of the service.
     */
    abstract protected function getService(string $name): Cipher;

    public function getDefaultCipherName(): string
    {
        return $this->defaultCipherName;
    }

    final public function getCipher(string|null $name = null): Cipher
    {
        $name ??= $this->defaultCipherName;

        if (! isset($this->cipherServices[$name])) {
            throw UnknownCipher::create($name, $this->getCipherNames());
        }

        return $this->getService($this->cipherServices[$name]);
    }

    /**
     * {@inheritDoc}
     */
    public function getCiphers(): array
    {
        $ciphers = [];

        foreach (array_keys($this->cipherServices) as $name) {
            $ciphers[$name] = $this->getCipher($name);
        }

        return $ciphers;
    }

    /**
     * {@inheritDoc}
     */
    public function getCipherNames(): array
    {
        return array_keys($this->cipherServices);
    }
}
