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

use Doctrine\ORM\Mapping\Driver\MappingDriver;
use Doctrine\ORM\Mapping\Exporter\ClassMetadataExporter;

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
     * @var ClassMetadataExporter
     */
    private $metadataExporter;

    /**
     * @param MappingDriver              $mappingDriver
     * @param ClassMetadataExporter|null $metadataExporter
     */
    public function __construct(
        MappingDriver $mappingDriver,
        ClassMetadataExporter $metadataExporter = null
    )
    {
        $this->mappingDriver    = $mappingDriver;
        $this->metadataExporter = $metadataExporter ?: new ClassMetadataExporter();
    }

    /**
     * Generates class metadata code.
     *
     * @param ClassMetadataDefinition $definition
     *
     * @return string
     */
    public function generate(ClassMetadataDefinition $definition) : string
    {
        $metadata = $this->mappingDriver->loadMetadataForClass(
            $definition->entityClassName,
            $definition->parentClassMetadata
        );

        return $this->metadataExporter->export($metadata);
    }
}
