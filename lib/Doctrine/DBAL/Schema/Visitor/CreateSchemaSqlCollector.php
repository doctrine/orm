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

namespace Doctrine\DBAL\Schema\Visitor;

use Doctrine\DBAL\Platforms\AbstractPlatform,
    Doctrine\DBAL\Schema\Table,
    Doctrine\DBAL\Schema\Schema,
    Doctrine\DBAL\Schema\Column,
    Doctrine\DBAL\Schema\ForeignKeyConstraint,
    Doctrine\DBAL\Schema\Constraint,
    Doctrine\DBAL\Schema\Sequence,
    Doctrine\DBAL\Schema\Index;

class CreateSchemaSqlCollector implements Visitor
{
    /**
     * @var array
     */
    private $_createTableQueries = array();

    /**
     * @var array
     */
    private $_createSequenceQueries = array();

    /**
     * @var array
     */
    private $_createFkConstraintQueries = array();

    /**
     *
     * @var \Doctrine\DBAL\Platforms\AbstractPlatform
     */
    private $_platform = null;

    /**
     * @param AbstractPlatform $platform
     */
    public function __construct(AbstractPlatform $platform)
    {
        $this->_platform = $platform;
    }

    /**
     * @param Schema $schema
     */
    public function acceptSchema(Schema $schema)
    {

    }

    /**
     * Generate DDL Statements to create the accepted table with all its dependencies.
     *
     * @param Table $table
     */
    public function acceptTable(Table $table)
    {
        $this->_createTableQueries = array_merge($this->_createTableQueries,
            $this->_platform->getCreateTableSQL($table)
        );
    }

    public function acceptColumn(Table $table, Column $column)
    {
        
    }

    /**
     * @param Table $localTable
     * @param ForeignKeyConstraint $fkConstraint
     */
    public function acceptForeignKey(Table $localTable, ForeignKeyConstraint $fkConstraint)
    {
        // Append the foreign key constraints SQL
        if ($this->_platform->supportsForeignKeyConstraints()) {
            $this->_createFkConstraintQueries = array_merge($this->_createFkConstraintQueries,
                (array) $this->_platform->getCreateForeignKeySQL($fkConstraint, $localTable->getName())
            );
        }
    }

    /**
     * @param Table $table
     * @param Index $index
     */
    public function acceptIndex(Table $table, Index $index)
    {
        
    }

    /**
     * @param Sequence $sequence
     */
    public function acceptSequence(Sequence $sequence)
    {
        $this->_createSequenceQueries = array_merge(
            $this->_createSequenceQueries, (array)$this->_platform->getCreateSequenceSQL($sequence)
        );
    }

    /**
     * @return array
     */
    public function resetQueries()
    {
        $this->_createTableQueries = array();
        $this->_createSequenceQueries = array();
        $this->_createFkConstraintQueries = array();
    }

    /**
     * Get all queries collected so far.
     *
     * @return array
     */
    public function getQueries()
    {
        return array_merge(
            $this->_createTableQueries,
            $this->_createSequenceQueries,
            $this->_createFkConstraintQueries
        );
    }
}