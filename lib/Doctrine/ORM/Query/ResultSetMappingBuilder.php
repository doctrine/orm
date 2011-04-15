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
 * and is licensed under the LGPL. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace Doctrine\ORM\Query;

use Doctrine\ORM\EntityManager;

/**
 * A ResultSetMappingBuilder uses the EntityManager to automatically populate entity fields
 *
 * @author Michael Ridgway <mcridgway@gmail.com>
 * @since 2.1
 */
class ResultSetMappingBuilder extends ResultSetMapping
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @param EntityManager
     */
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * Adds a root entity and all of its fields to the result set.
     *
     * @param string $class The class name of the root entity.
     * @param string $alias The unique alias to use for the root entity.
     * @param array $renamedColumns Columns that have been renamed (tableColumnName => queryColumnName)
     */
    public function addRootEntityFromClassMetadata($class, $alias, $renamedColumns = array())
    {
        $this->addEntityResult($class, $alias);
        $classMetadata = $this->em->getClassMetadata($class);
        if ($classMetadata->isInheritanceTypeSingleTable() || $classMetadata->isInheritanceTypeJoined()) {
            throw new \InvalidArgumentException('ResultSetMapping builder does not currently support inheritance.');
        }
        $platform = $this->em->getConnection()->getDatabasePlatform();
        foreach ($classMetadata->getColumnNames() AS $columnName) {
            $propertyName = $classMetadata->getFieldName($columnName);
            if (isset($renamedColumns[$columnName])) {
                $columnName = $renamedColumns[$columnName];
            }
            if (isset($this->fieldMappings[$columnName])) {
                throw new \InvalidArgumentException("The column '$columnName' conflicts with another column in the mapper.");
            }
            $this->addFieldResult($alias, $platform->getSQLResultCasing($columnName), $propertyName);
        }
    }

    /**
     * Adds a joined entity and all of its fields to the result set.
     *
     * @param string $class The class name of the joined entity.
     * @param string $alias The unique alias to use for the joined entity.
     * @param string $parentAlias The alias of the entity result that is the parent of this joined result.
     * @param object $relation The association field that connects the parent entity result with the joined entity result.
     * @param array $renamedColumns Columns that have been renamed (tableColumnName => queryColumnName)
     */
    public function addJoinedEntityFromClassMetadata($class, $alias, $parentAlias, $relation, $renamedColumns = array())
    {
        $this->addJoinedEntityResult($class, $alias, $parentAlias, $relation);
        $classMetadata = $this->em->getClassMetadata($class);
        if ($classMetadata->isInheritanceTypeSingleTable() || $classMetadata->isInheritanceTypeJoined()) {
            throw new \InvalidArgumentException('ResultSetMapping builder does not currently support inheritance.');
        }
        $platform = $this->em->getConnection()->getDatabasePlatform();
        foreach ($classMetadata->getColumnNames() AS $columnName) {
            $propertyName = $classMetadata->getFieldName($columnName);
            if (isset($renamedColumns[$columnName])) {
                $columnName = $renamedColumns[$columnName];
            }
            if (isset($this->fieldMappings[$columnName])) {
                throw new \InvalidArgumentException("The column '$columnName' conflicts with another column in the mapper.");
            }
            $this->addFieldResult($alias, $platform->getSQLResultCasing($columnName), $propertyName);
        }
    }
}