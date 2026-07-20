<?php

declare(strict_types=1);

namespace Doctrine\ORM\Encrypt\Exception;

use Doctrine\ORM\Exception\ORMException;
use LogicException;

final class EncryptConfigurationMissing extends LogicException implements ORMException
{
    public static function forCipherRegistry(): self
    {
        return new self(
            'Cipher registry is not configured. Call Configuration::setCipherRegistry() to set it.',
        );
    }

    public static function forKeyProviderRegistry(): self
    {
        return new self(
            'Key provider registry is not configured. Call Configuration::setKeyProviderRegistry() to set it.',
        );
    }
}
