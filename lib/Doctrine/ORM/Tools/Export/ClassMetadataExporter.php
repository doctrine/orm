<?php

declare(strict_types=1);

namespace Doctrine\ORM\Tools\Export;

/**
 * Class used for converting your mapping information between the
 * supported formats: xml and php/annotation.
 *
 * @link    www.doctrine-project.org
 * @since   2.0
 * @author  Jonathan Wage <jonwage@gmail.com>
 */
class ClassMetadataExporter
{
    /**
     * @var array
     */
    private static $_exporterDrivers = [
        'xml' => Driver\XmlExporter::class,
        'php' => Driver\PhpExporter::class,
        'annotation' => Driver\AnnotationExporter::class
    ];

    /**
     * Registers a new exporter driver class under a specified name.
     *
     * @param string $name
     * @param string $class
     *
     * @return void
     */
    public static function registerExportDriver($name, $class)
    {
        self::$_exporterDrivers[$name] = $class;
    }

    /**
     * Gets an exporter driver instance.
     *
     * @param string      $type The type to get (xml, etc.).
     * @param string|null $dest The directory where the exporter will export to.
     *
     * @return Driver\AbstractExporter
     *
     * @throws ExportException
     */
    public function getExporter($type, $dest = null)
    {
        if ( ! isset(self::$_exporterDrivers[$type])) {
            throw ExportException::invalidExporterDriverType($type);
        }

        $class = self::$_exporterDrivers[$type];

        return new $class($dest);
    }
}
