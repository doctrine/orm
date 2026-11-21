<?php

declare(strict_types=1);

namespace Doctrine\ORM\Exception;

use Doctrine\ORM\Collection\DelegateCollectionFactory;
use LogicException;

use function sprintf;

final class UninstantiableCollection extends LogicException implements ORMException
{
    public static function withoutArguments(string $collectionClass): self
    {
        return new self(sprintf(
            'The custom collection %s cannot be created without arguments, as its constructor is not public or has required parameters. Create it with a %s, configured with Configuration::setDelegateCollectionFactory().',
            $collectionClass,
            DelegateCollectionFactory::class,
        ));
    }
}
