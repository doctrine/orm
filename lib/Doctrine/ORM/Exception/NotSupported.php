<?php

declare(strict_types=1);

namespace Doctrine\ORM\Exception;

final class NotSupported extends ORMException
{
    public static function create(): self
    {
        return new self('This behaviour is (currently) not supported by Doctrine 2');
    }

    public static function createForDbal3(): self
    {
        return new self('Feature was deprecated in doctrine/dbal 2.x and is not supported by installed doctrine/dbal:3.x, please see the doctrine/deprecations logs for new alternative approaches.');
    }
}
