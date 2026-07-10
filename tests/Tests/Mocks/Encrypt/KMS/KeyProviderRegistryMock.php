<?php

declare(strict_types=1);

namespace Doctrine\Tests\Mocks\Encrypt\KMS;

use Doctrine\ORM\Encrypt\KMS\AbstractKeyProviderRegistry;
use Doctrine\ORM\Encrypt\KMS\KeyProvider;

class KeyProviderRegistryMock extends AbstractKeyProviderRegistry
{
    public KeyProvider $default;
    public KeyProvider $a;
    public KeyProvider $b;

    public function __construct(string $defaultKeyProviderName = 'alias')
    {
        $this->default = new KeyProviderMock('secret');
        $this->a       = new KeyProviderMock('a');
        $this->b       = new KeyProviderMock('b');

        parent::__construct([
            'alias' => 'service.default',
            'alias-a' => 'service.a',
            'alias-b' => 'service.b',
        ], $defaultKeyProviderName);
    }

    protected function getService(string $name): KeyProvider
    {
        return match ($name) {
            'service.default' => $this->default,
            'service.a' => $this->a,
            'service.b' => $this->b,
        };
    }
}
