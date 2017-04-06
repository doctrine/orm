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
     * @var ClassMetadataDriver
     */
    private $metadataDriver;

    /**
     * @var ClassMetadataExporter
     */
    private $metadataExporter;

    /**
     * @param ClassMetadataDriver        $metadataDriver
     * @param ClassMetadataExporter|null $metadataExporter
     */
    public function __construct(
        ClassMetadataDriver $metadataDriver,
        ClassMetadataExporter $metadataExporter = null
    )
    {
        $this->metadataDriver   = $metadataDriver;
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
        $metadata   = $this->metadataDriver->getClassMetadata($definition->entityClassName);
        $sourceCode = $this->metadataExporter->export($metadata);

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
