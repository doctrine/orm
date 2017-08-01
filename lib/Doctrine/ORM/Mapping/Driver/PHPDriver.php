<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping\Driver;

use Doctrine\ORM\Mapping;

/**
 * The PHPDriver includes php files which just populate ClassMetadataInfo
 * instances with plain PHP code.
 *
 * @link   www.doctrine-project.org
 * @since  2.0
 * @author Benjamin Eberlei <kontakt@beberlei.de>
 * @author Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author Jonathan H. Wage <jonwage@gmail.com>
 * @author Roman Borschel <roman@code-factory.org>
 */
class PHPDriver extends FileDriver
{
    /**
     * @var Mapping\ClassMetadata
     */
    protected $metadata;

    /**
     * {@inheritDoc}
     */
    public function __construct($locator)
    {
        parent::__construct($locator, '.php');
    }

    /**
     * {@inheritDoc}
     */
    public function loadMetadataForClass(
        string $className,
        Mapping\ClassMetadata $metadata,
        Mapping\ClassMetadataBuildingContext $metadataBuildingContext
    )
    {
        $this->metadata = $metadata;

        $this->loadMappingFile($this->locator->findMappingFile($className));
    }

    /**
     * {@inheritDoc}
     */
    protected function loadMappingFile($file)
    {
        $metadata = $this->metadata;

        include $file;

        return [$metadata->getClassName() => $metadata];
    }
}
