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

namespace Doctrine\ORM\Mapping;

use Doctrine\ORM\Exceptions\MappingException;

/**
 * A many-to-many mapping describes the mapping between two collections of
 * entities.
 *
 * @since 2.0
 * @author Roman Borschel <roman@code-factory.org>
 */
class ManyToManyMapping extends AssociationMapping
{
    /**
     * The key columns of the source table.
     */
    private $_sourceKeyColumns = array();

    /**
     * The key columns of the target table.
     */
    private $_targetKeyColumns = array();

    /**
     * Maps the columns in the source table to the columns in the relation table.
     */
    private $_sourceToRelationKeyColumns = array();

    /**
     * Maps the columns in the target table to the columns in the relation table.
     */
    private $_targetToRelationKeyColumns = array();
    
    /**
     * Initializes a new ManyToManyMapping.
     *
     * @param array $mapping  The mapping information.
     */
    public function __construct(array $mapping)
    {
        parent::__construct($mapping);
    }
    
    /**
     * Validates and completes the mapping.
     *
     * @param array $mapping
     * @override
     */
    protected function _validateAndCompleteMapping(array $mapping)
    {
        parent::_validateAndCompleteMapping($mapping);
        if ($this->isOwningSide()) {
            // owning side MUST have a join table
            if ( ! isset($mapping['joinTable'])) {
                throw MappingException::joinTableRequired($mapping['fieldName']);
            }
            // owning side MUST specify joinColumns
            if ( ! isset($mapping['joinTable']['joinColumns'])) {
                throw MappingException::invalidMapping($this->_sourceFieldName);
            }
            foreach ($mapping['joinTable']['joinColumns'] as $joinColumn) {
                $this->_sourceToRelationKeyColumns[$joinColumn['referencedColumnName']] = $joinColumn['name'];
            }
            $this->_sourceKeyColumns = array_keys($this->_sourceToRelationKeyColumns);
            // owning side MUST specify inverseJoinColumns
            if ( ! isset($mapping['joinTable']['inverseJoinColumns'])) {
                throw MappingException::invalidMapping($this->_sourceFieldName);
            }
            foreach ($mapping['joinTable']['inverseJoinColumns'] as $inverseJoinColumn) {
                $this->_targetToRelationKeyColumns[$inverseJoinColumn['referencedColumnName']] = $inverseJoinColumn['name'];
            }
            $this->_targetKeyColumns = array_keys($this->_targetToRelationKeyColumns);
        }
    }

    public function getSourceToRelationKeyColumns()
    {
        return $this->_sourceToRelationKeyColumns;
    }

    public function getTargetToRelationKeyColumns()
    {
        return $this->_targetToRelationKeyColumns;
    }

    public function getSourceKeyColumns()
    {
        return $this->_sourceKeyColumns;
    }

    public function getTargetKeyColumns()
    {
        return $this->_targetKeyColumns;
    }

    public function lazyLoadFor($entity, $entityManager)
    {
        
    }

    /**
     * {@inheritdoc}
     *
     * @override
     */
    public function isManyToMany()
    {
        return true;
    }
}

