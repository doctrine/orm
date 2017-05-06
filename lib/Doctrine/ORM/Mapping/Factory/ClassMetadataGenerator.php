<?php

declare(strict_types = 1);

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

namespace Doctrine\ORM\Mapping\Factory;

use Doctrine\Common\Persistence\Mapping\Driver\MappingDriver;
use Doctrine\ORM\Mapping\Builder\ClassMetadataExporter;

/**
 * This factory is used to generate metadata classes.
 *
 * @package Doctrine\ORM\Mapping\Factory
 *
 * @author Guilherme Blanco <guilhermeblanco@hotmail.com>
 */
class ClassMetadataGenerator
{
    /**
     * @var MappingDriver
     */
    protected $mappingDriver;

    /**
     * @var NamingStrategy
     */
    protected $namingStrategy;

    /**
     * @var ClassMetadataExporter
     */
    private $metadataExporter;

    /**
     * @param MappingDriver              $mappingDriver
     * @param NamingStrategy             $namingStrategy
     * @param ClassMetadataExporter|null $metadataExporter
     */
    public function __construct(
        MappingDriver $mappingDriver,
        NamingStrategy $namingStrategy,
        ClassMetadataExporter $metadataExporter = null
    )
    {
        $this->mappingDriver    = $mappingDriver;
        $this->namingStrategy   = $namingStrategy;
        $this->metadataExporter = $metadataExporter ?: new ClassMetadataExporter();
    }

    /**
     * @param string                  $path
     * @param ClassMetadataDefinition $definition
     *
     * @throws \RuntimeException
     */
    public function generate(string $path, ClassMetadataDefinition $definition)
    {
        $builder    = $this->mappingDriver->loadMetadataForClass($definition->entityClassName, $definition->parent);
        $metadata   = $builder->build($this->namingStrategy);
        $sourceCode = $this->metadataExporter->export($metadata); // @todo guilhermeblanco Pass class name to exporter

        $this->ensureDirectoryIsReady(dirname($path));

        $tmpFileName = $path . '.' . uniqid('', true);

        file_put_contents($tmpFileName, $sourceCode);
        @chmod($tmpFileName, 0664);
        rename($tmpFileName, $path);
    }

    /**
     * @param string $directory
     *
     * @throws \RuntimeException
     */
    protected function ensureDirectoryIsReady(string $directory)
    {
        if (! is_dir($directory) && (false === @mkdir($directory, 0775, true))) {
            throw new \RuntimeException(sprintf('Your metadata directory "%s" must be writable', $directory));
        }

        if (! is_writable($directory)) {
            throw new \RuntimeException(sprintf('Your proxy directory "%s" must be writable', $directory));
        }
    }
}
