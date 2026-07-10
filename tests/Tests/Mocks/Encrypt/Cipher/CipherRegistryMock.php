<?php

declare(strict_types=1);

namespace Doctrine\Tests\Mocks\Encrypt\Cipher;

use Doctrine\ORM\Encrypt\Cipher\AbstractCipherRegistry;
use Doctrine\ORM\Encrypt\Cipher\Cipher;

final class CipherRegistryMock extends AbstractCipherRegistry
{
    public Cipher $default;
    public Cipher $a;
    public Cipher $b;

    public function __construct(string $defaultCipherName = 'alias')
    {
        $this->default = new CipherMock();
        $this->a       = new CipherMock();
        $this->b       = new CipherMock();

        parent::__construct([
            'alias' => 'service.default',
            'alias-a' => 'service.a',
            'alias-b' => 'service.b',
        ], $defaultCipherName);
    }

    protected function getService(string $name): Cipher
    {
        return match ($name) {
            'service.default' => $this->default,
            'service.a' => $this->a,
            'service.b' => $this->b,
        };
    }
}
