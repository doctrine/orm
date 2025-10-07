<?php

declare(strict_types=1);

namespace Doctrine\ORM\Tools\Export\Driver;

use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\ORM\Tools\EntityGenerator;
use RuntimeException;

use function str_replace;

/**
 * ClassMetadata exporter for PHP classes with annotations.
 *
 * @deprecated 2.7 This class is being removed from the ORM and won't have any replacement
 *
 * @link    www.doctrine-project.org
 */
class AnnotationExporter extends AbstractExporter
{
    /** @var string */
    protected $_extension = '.php';

    /** @var EntityGenerator|null */
    private $entityGenerator;

    /**
     * {@inheritDoc}
     */
    public function exportClassMetadata(ClassMetadataInfo $metadata)
    {
        if (! $this->entityGenerator) {
            throw new RuntimeException('For the AnnotationExporter you must set an EntityGenerator instance with the setEntityGenerator() method.');
        }

        $this->entityGenerator->setGenerateAnnotations(true);
        $this->entityGenerator->setGenerateStubMethods(false);
        $this->entityGenerator->setRegenerateEntityIfExists(false);
        $this->entityGenerator->setUpdateEntityIfExists(false);

        return $this->entityGenerator->generateEntityClass($metadata);
    }

    /** @return string */
    protected function _generateOutputPath(ClassMetadataInfo $metadata)
    {
        return $this->_outputDir . '/' . str_replace('\\', '/', $metadata->name) . $this->_extension;
    }

    /** @return void */
    public function setEntityGenerator(EntityGenerator $entityGenerator)
    {
        $this->entityGenerator = $entityGenerator;
    }
}
