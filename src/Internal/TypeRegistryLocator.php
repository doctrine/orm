<?php

declare(strict_types=1);

namespace Doctrine\ORM\Internal;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\TypeRegistry;

use function method_exists;

/**
 * Resolves the {@see TypeRegistry} owned by a DBAL connection.
 *
 * @internal
 */
final class TypeRegistryLocator
{
    public static function fromConnection(Connection $connection): TypeRegistry
    {
        $configuration = $connection->getConfiguration();

        // The method_exists() check is for DBAL < 4.5 compatibility, where Configuration::getTypeRegistry() does not exist yet.
        if (method_exists($configuration, 'getTypeRegistry')) {
            return $configuration->getTypeRegistry();
        }

        return Type::getTypeRegistry();
    }
}
