<?php

declare(strict_types=1);

namespace Doctrine\ORM\Tools\Export\Driver;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Tools\EntityGenerator;

/**
 * ClassMetadata exporter for PHP classes with annotations.
 *
 * @link    www.doctrine-project.org
 * @since   2.0
 * @author  Jonathan Wage <jonwage@gmail.com>
 */
class AnnotationExporter extends AbstractExporter
{
    /**
     * @var string
     */
    protected $extension = '.php';

    /**
     * @var EntityGenerator|null
     */
    private $entityGenerator;

    /**
     * {@inheritdoc}
     */
    public function exportClassMetadata(ClassMetadata $metadata)
    {
        if ( ! $this->entityGenerator) {
            throw new \RuntimeException('For the AnnotationExporter you must set an EntityGenerator instance with the setEntityGenerator() method.');
        }

        $this->entityGenerator->setGenerateAnnotations(true);
        $this->entityGenerator->setGenerateStubMethods(false);
        $this->entityGenerator->setRegenerateEntityIfExists(false);
        $this->entityGenerator->setUpdateEntityIfExists(false);

        return $this->entityGenerator->generateEntityClass($metadata);
    }

    /**
     * @param ClassMetadata $metadata
     *
     * @return string
     */
    protected function generateOutputPath(ClassMetadata $metadata)
    {
        return $this->outputDir . '/' . str_replace('\\', '/', $metadata->getClassName()) . $this->extension;
    }

    /**
     * @param EntityGenerator $entityGenerator
     *
     * @return void
     */
    public function setEntityGenerator(EntityGenerator $entityGenerator)
    {
        $this->entityGenerator = $entityGenerator;
    }
}
