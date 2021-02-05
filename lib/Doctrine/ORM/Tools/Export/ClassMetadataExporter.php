<?php

/*
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the MIT license. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace Doctrine\ORM\Tools\Export;

use function trigger_error;

use const E_USER_DEPRECATED;

/**
 * Class used for converting your mapping information between the
 * supported formats: yaml, xml, and php/annotation.
 *
 * @deprecated 2.7 This class is being removed from the ORM and won't have any replacement
 *
 * @link    www.doctrine-project.org
 */
class ClassMetadataExporter
{
    /** @var array<string,string> */
    private static $_exporterDrivers = [
        'xml' => Driver\XmlExporter::class,
        'yaml' => Driver\YamlExporter::class,
        'yml' => Driver\YamlExporter::class,
        'php' => Driver\PhpExporter::class,
        'annotation' => Driver\AnnotationExporter::class,
    ];

    public function __construct()
    {
        @trigger_error(self::class . ' is deprecated and will be removed in Doctrine ORM 3.0', E_USER_DEPRECATED);
    }

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
     * @param string      $type The type to get (yml, xml, etc.).
     * @param string|null $dest The directory where the exporter will export to.
     *
     * @return Driver\AbstractExporter
     *
     * @throws ExportException
     */
    public function getExporter($type, $dest = null)
    {
        if (! isset(self::$_exporterDrivers[$type])) {
            throw ExportException::invalidExporterDriverType($type);
        }

        $class = self::$_exporterDrivers[$type];

        return new $class($dest);
    }
}
