<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Encrypt;

use Doctrine\ORM\Encrypt\Cipher\AbstractCipherRegistry;
use Doctrine\ORM\Encrypt\Cipher\Cipher;

use function array_combine;
use function array_keys;

final class EncryptTestCipherRegistry extends AbstractCipherRegistry
{
    /** @param non-empty-array<string, Cipher> $ciphers */
    public function __construct(
        private readonly array $ciphers,
        string $defaultCipherName,
    ) {
        parent::__construct(
            array_combine($names = array_keys($this->ciphers), $names),
            $defaultCipherName,
        );
    }

    protected function getService(string $name): Cipher
    {
        return $this->ciphers[$name];
    }
}
