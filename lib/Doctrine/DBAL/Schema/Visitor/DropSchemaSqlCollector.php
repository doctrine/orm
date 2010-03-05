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

/**
 * Gather SQL statements that allow to completly drop the current schema.
 *
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link    www.doctrine-project.org
 * @since   2.0
 * @version $Revision$
 * @author  Benjamin Eberlei <kontakt@beberlei.de>
 */
class DropSchemaSqlCollector implements Visitor
{
    /**
     * @var array
     */
    private $_constraints = array();
    
    /**
     * @var array
     */
    private $_sequences = array();

    /**
     * @var array
     */
    private $_tables = array();

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
     * @param Table $table
     */
    public function acceptTable(Table $table)
    {
        $this->_tables[] = $this->_platform->getDropTableSQL($table->getName());
    }

    /**
     * @param Column $column
     */
    public function acceptColumn(Table $table, Column $column)
    {
        
    }

    /**
     * @param Table $localTable
     * @param ForeignKeyConstraint $fkConstraint
     */
    public function acceptForeignKey(Table $localTable, ForeignKeyConstraint $fkConstraint)
    {
        if (strlen($fkConstraint->getName()) == 0) {
            throw SchemaException::namedForeignKeyRequired($localTable, $fkConstraint);
        }

        $this->_constraints[] = $this->_platform->getDropForeignKeySQL($fkConstraint->getName(), $localTable->getName());
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
        $this->_sequences[] = $this->_platform->getDropSequenceSQL($sequence->getName());
    }

    /**
     * @return array
     */
    public function clearQueries()
    {
        $this->_constraints = $this->_sequences = $this->_tables = array();
    }

    /**
     * @return array
     */
    public function getQueries()
    {
        return array_merge(
            $this->_constraints,
            $this->_sequences,
            $this->_tables
        );
    }
}
