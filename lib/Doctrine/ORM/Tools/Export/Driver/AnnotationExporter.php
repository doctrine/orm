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

use Doctrine\ORM\Mapping\ClassMetadataInfo,
    Doctrine\ORM\Mapping\AssociationMapping,
    Doctrine\ORM\Tools\EntityGenerator;

/**
 * ClassMetadata exporter for PHP classes with annotations
 *
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link    www.doctrine-project.org
 * @since   2.0
 * @version $Revision$
 * @author  Jonathan Wage <jonwage@gmail.com>
 */
class AnnotationExporter extends AbstractExporter
{
    protected $_extension = '.php';
    private $_generator;

    public function __construct($dir = null)
    {
        parent::__construct($dir);
        $this->_generator = new EntityGenerator();
    }

    /**
     * Converts a single ClassMetadata instance to the exported format
     * and returns it
     *
     * @param ClassMetadataInfo $metadata 
     * @return string $exported
     */
    public function exportClassMetadata(ClassMetadataInfo $metadata)
    {
        $this->_generator->setGenerateAnnotations(true);
        $this->_generator->setGenerateStubMethods(false);
        $this->_generator->setRegenerateEntityIfExists(false);
        $this->_generator->setUpdateEntityIfExists(false);

        return $this->_generator->generateEntityClass($metadata);
    }

    protected function _generateOutputPath(ClassMetadataInfo $metadata)
    {
        return $this->_outputDir . '/' . str_replace('\\', '/', $metadata->name) . $this->_extension;
    }

    /**
     * Set the number of spaces the exported class should have
     *
     * @param integer $numSpaces 
     * @return void
     */
    public function setNumSpaces($numSpaces)
    {
        $this->_generator->setNumSpaces($numSpaces);
    }

    /**
     * Set the name of the class the generated classes should extend from
     *
     * @return void
     */
    public function setClassToExtend($classToExtend)
    {
        $this->_generator->setClassToExtend($classToExtend);
    }
}