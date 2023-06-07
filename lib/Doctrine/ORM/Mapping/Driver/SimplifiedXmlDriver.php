<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping\Driver;

use Doctrine\Persistence\Mapping\Driver\SymfonyFileLocator;
use InvalidArgumentException;

use function sprintf;

/**
 * XmlDriver that additionally looks for mapping information in a global file.
 */
class SimplifiedXmlDriver extends XmlDriver
{
    public const DEFAULT_FILE_EXTENSION = '.orm.xml';

    /**
     * {@inheritDoc}
     */
    public function __construct($prefixes, $fileExtension = self::DEFAULT_FILE_EXTENSION, bool $isXsdValidationEnabled = true)
    {
        if (! $isXsdValidationEnabled) {
            throw new InvalidArgumentException(sprintf(
                'The $isXsdValidationEnabled argument is no longer supported, make sure to omit it when calling %s.',
                __METHOD__,
            ));
        }

        $locator = new SymfonyFileLocator((array) $prefixes, $fileExtension);

        parent::__construct($locator, $fileExtension);
    }
}
