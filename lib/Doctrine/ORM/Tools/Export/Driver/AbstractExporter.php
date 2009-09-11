<?php

/*
 *  $Id$
 *
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
 * and is licensed under the LGPL. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace Doctrine\ORM\Tools\Export\Driver;

use Doctrine\ORM\Mapping\ClassMetadata;

/**
 * Abstract base class which is to be used for the Exporter drivers
 * which can be found in Doctrine\ORM\Tools\Export\Driver
 *
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link    www.doctrine-project.org
 * @since   2.0
 * @version $Revision$
 * @author  Jonathan Wage <jonwage@gmail.com>
 */
abstract class AbstractExporter
{
    protected $_metadatas = array();
    protected $_outputDir;
    protected $_extension;

    public function __construct(array $metadatas, $dir = null)
    {
        $this->_metadatas = $metadatas;
        $this->_outputDir = $dir;
    }

    /**
     * Set the directory to output the mapping files to
     *
     *     [php]
     *     $exporter = new YamlExporter($metadatas);
     *     $exporter->setOutputDir(__DIR__ . '/yaml');
     *     $exporter->export();
     *
     * @param string $dir 
     * @return void
     */
    public function setOutputDir($dir)
    {
        $this->_outputDir = $dir;
    }

    /**
     * Export each ClassMetadata instance to a single Doctrine Mapping file
     * named after the entity
     *
     * @return void
     */
    public function export()
    {
        foreach ($this->_metadatas as $metadata) {
            $outputPath = $this->_outputDir . '/' . str_replace('\\', '.', $metadata->name) . $this->_extension;
            $output = $this->exportClassMetadata($metadata);
            file_put_contents($outputPath, $output);
        }
    }

    /**
     * Set the directory to output the mapping files to
     *
     *     [php]
     *     $exporter = new YamlExporter($metadatas, __DIR__ . '/yaml');
     *     $exporter->setExtension('.yml');
     *     $exporter->export();
     *
     * @param string $extension
     * @return void
     */
    public function setExtension($extension)
    {
        $this->_extension = $extension;
    }

    /**
     * Converts a single ClassMetadata instance to the exported format
     * and returns it
     *
     * @param ClassMetadata $metadata 
     * @return mixed $exported
     */
    abstract public function exportClassMetadata(ClassMetadata $metadata);
}