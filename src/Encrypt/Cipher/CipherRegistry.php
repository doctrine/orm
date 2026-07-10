<?php

declare(strict_types=1);

namespace Doctrine\ORM\Encrypt\Cipher;

use Doctrine\ORM\Encrypt\Cipher\Exception\UnknownCipher;

interface CipherRegistry
{
    public function getDefaultCipherName(): string;

    /**
     * @param string|null $name null resolves to the default cipher
     *
     * @throws UnknownCipher when $name is not registered.
     */
    public function getCipher(string|null $name = null): Cipher;

    /**
     * Get all Ciphers in this registry.
     *
     * @return array<string, Cipher> indexed by cipher name
     */
    public function getCiphers(): array;

    /** @return list<string> */
    public function getCipherNames(): array;
}
