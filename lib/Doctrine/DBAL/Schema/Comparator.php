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

namespace Doctrine\DBAL\Schema;

/**
 * Compare to Schemas and return an instance of SchemaDiff
 *
 * @copyright Copyright (C) 2005-2009 eZ Systems AS. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link    www.doctrine-project.org
 * @since   2.0
 * @version $Revision$
 * @author  Benjamin Eberlei <kontakt@beberlei.de>
 */
class Comparator
{
    /**
     * @var array
     */
    private $_checkColumnPlatformOptions = array();

    /**
     * @param string $optionName
     */
    public function addColumnPlatformOptionCheck($optionName)
    {
        $this->_checkColumnPlatformOptions[] = $optionName;
    }

    /**
     * @param Schema $fromSchema
     * @param Schema $toSchema
     * @return SchemaDiff
     */
    static public function compareSchemas( Schema $fromSchema, Schema $toSchema )
    {
        $c = new self();
        return $c->compare($fromSchema, $toSchema);
    }

    /**
     * Returns a SchemaDiff object containing the differences between the schemas $fromSchema and $toSchema.
     *
     * The returned diferences are returned in such a way that they contain the
     * operations to change the schema stored in $fromSchema to the schema that is
     * stored in $toSchema.
     *
     * @param Schema $fromSchema
     * @param Schema $toSchema
     *
     * @return SchemaDiff
     */
    public function compare( Schema $fromSchema, Schema $toSchema )
    {
        $diff = new SchemaDiff();

        foreach ( $toSchema->getTables() as $tableName => $table ) {
            if ( !$fromSchema->hasTable($tableName) ) {
                $diff->newTables[$tableName] = $table;
            } else {
                $tableDifferences = $this->diffTable( $fromSchema->getTable($tableName), $table );
                if ( $tableDifferences !== false ) {
                    $diff->changedTables[$tableName] = $tableDifferences;
                }
            }
        }

        /* Check if there are tables removed */
        foreach ( $fromSchema->getTables() as $tableName => $table ) {
            if ( !$toSchema->hasTable($tableName) ) {
                $diff->removedTables[$tableName] = $table;
            }
        }

        foreach ( $toSchema->getSequences() AS $sequenceName => $sequence) {
            if (!$fromSchema->hasSequence($sequenceName)) {
                $diff->newSequences[] = $sequence;
            } else {
                if ($this->diffSequence($sequence, $fromSchema->getSequence($sequenceName))) {
                    $diff->changedSequences[] = $fromSchema->getSequence($sequenceName);
                }
            }
        }

        foreach ($fromSchema->getSequences() AS $sequenceName => $sequence) {
            if (!$toSchema->hasSequence($sequenceName)) {
                $diff->removedSequences[] = $sequence;
            }
        }

        return $diff;
    }

    /**
     *
     * @param Sequence $sequence1
     * @param Sequence $sequence2
     */
    public function diffSequence($sequence1, $sequence2)
    {
        if($sequence1->getAllocationSize() != $sequence2->getAllocationSize()) {
            return true;
        }

        if($sequence1->getInitialValue() != $sequence2->getInitialValue()) {
            return true;
        }

        return false;
    }

    /**
     * Returns the difference between the tables $table1 and $table2.
     *
     * If there are no differences this method returns the boolean false.
     *
     * @param Table $table1
     * @param Table $table2
     *
     * @return bool|TableDiff
     */
    public function diffTable( Table $table1, Table $table2 )
    {
        $changes = 0;
        $tableDifferences = new TableDiff();

        /* See if all the fields in table 1 exist in table 2 */
        foreach ( $table2->getColumns() as $columnName => $column ) {
            if ( !$table1->hasColumn($columnName) ) {
                $tableDifferences->addedFields[$columnName] = $column;
                $changes++;
            }
        }
        /* See if there are any removed fields in table 2 */
        foreach ( $table1->getColumns() as $columnName => $column ) {
            if ( !$table2->hasColumn($columnName) ) {
                $tableDifferences->removedFields[$columnName] = true;
                $changes++;
            }
        }
        /* See if there are any changed fieldDefinitioninitions */
        foreach ( $table1->getColumns() as $columnName => $column ) {
            if ( $table2->hasColumn($columnName) ) {
                if ( $this->diffColumn( $column, $table2->getColumn($columnName) ) ) {
                    $tableDifferences->changedFields[$columnName] = $table2->getColumn($columnName);
                    $changes++;
                }
            }
        }

        $table1Indexes = $table1->getIndexes();
        $table2Indexes = $table2->getIndexes();

        /* See if all the indexes in table 1 exist in table 2 */
        foreach ( $table2Indexes as $indexName => $indexDefinition ) {
            if ( !isset( $table1Indexes[$indexName] ) ) {
                $tableDifferences->addedIndexes[$indexName] = $indexDefinition;
                $changes++;
            }
        }
        /* See if there are any removed indexes in table 2 */
        foreach ( $table1Indexes as $indexName => $indexDefinition ) {
            if ( !isset( $table2Indexes[$indexName] ) ) {
                $tableDifferences->removedIndexes[$indexName] = true;
                $changes++;
            }
        }
        /* See if there are any changed indexDefinitions */
        foreach ( $table1Indexes as $indexName => $indexDefinition ) {
            if ( isset( $table2Indexes[$indexName] ) ) {
                if ( $this->diffIndex( $indexDefinition, $table2Indexes[$indexName] ) ) {
                    $tableDifferences->changedIndexes[$indexName] = $table2Indexes[$indexName];
                    $changes++;
                }
            }
        }

        foreach ($table2->getForeignKeys() AS $constraint) {
            $fkName = $constraint->getName();
            if (!$table1->hasForeignKey($fkName)) {
                $tableDifferences->addedForeignKeys[$fkName] = $constraint;
                $changes++;
            } else {
                if ($this->diffForeignKey($constraint, $table1->getForeignKey($fkName))) {
                    $tableDifferences->changedForeignKeys[$fkName] = $constraint;
                    $changes++;
                }
            }
        }

        foreach ($table1->getForeignKeys() AS $constraint) {
            $fkName = $constraint->getName();
            if (!$table2->hasForeignKey($fkName)) {
                $tableDifferences->removedForeignKeys[$fkName] = $constraint;
                $changes++;
            }
        }

        return $changes ? $tableDifferences : false;
    }

    /**
     * @param ForeignKeyConstraint $key1
     * @param ForeignKeyConstraint $key2
     * @return bool
     */
    public function diffForeignKey($key1, $key2)
    {
        if ($key1->getLocalColumns() != $key2->getLocalColumns()) {
            return true;
        }
        
        if ($key1->getForeignColumns() != $key2->getForeignColumns()) {
            return true;
        }

        if ($key1->hasOption('onUpdate') != $key2->hasOption('onUpdate')) {
            return true;
        }

        if ($key1->getOption('onUpdate') != $key2->getOption('onUpdate')) {
            return true;
        }

        if ($key1->hasOption('onDelete') != $key2->hasOption('onDelete')) {
            return true;
        }

        if ($key1->getOption('onDelete') != $key2->getOption('onDelete')) {
            return true;
        }

        return false;
    }

    /**
     * Returns the difference between the fields $field1 and $field2.
     *
     * If there are differences this method returns $field2, otherwise the
     * boolean false.
     *
     * @param Column $column1
     * @param Column $column2
     *
     * @return bool
     */
    public function diffColumn( Column $column1, Column $column2 )
    {
        if ( $column1->getType() != $column2->getType() ) {
            return true;
        }

        if ($column1->getNotnull() != $column2->getNotnull()) {
            return true;
        }

        if ($column1->getDefault() != $column2->getDefault()) {
            return true;
        }

        if ($column1->getUnsigned() != $column2->getUnsigned()) {
            return true;
        }

        if ($column1->getType() instanceof \Doctrine\DBAL\Types\StringType) {
            if ($column1->getLength() != $column2->getLength()) {
                return true;
            }

            if ($column1->getFixed() != $column2->getFixed()) {
                return true;
            }
        }

        if ($column1->getType() instanceof \Doctrine\DBAL\Types\DecimalType) {
            if ($column1->getPrecision() != $column2->getPrecision()) {
                return true;
            }
            if ($column1->getScale() != $column2->getScale()) {
                return true;
            }
        }

        foreach ($this->_checkColumnPlatformOptions AS $optionName) {
            if ($column1->hasPlatformOption($optionName) != $column2->hasPlatformOption($optionName)) {
                return true;
            }

            if ($column1->getPlatformOption($optionName) != $column2->getPlatformOption($optionName)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Finds the difference between the indexes $index1 and $index2.
     *
     * Compares $index1 with $index2 and returns $index2 if there are any
     * differences or false in case there are no differences.
     *
     * @param Index $index1
     * @param Index $index2
     * @return bool
     */
    public function diffIndex( Index $index1, Index $index2 )
    {
        if($index1->isPrimary() != $index2->isPrimary()) {
            return true;
        }
        if($index1->isUnique() != $index2->isUnique()) {
            return true;
        }

        // Check for removed index fields in $index2
        $index1Columns = $index1->getColumns();
        for($i = 0; $i < count($index1Columns); $i++) {
            $indexColumn = $index1Columns[$i];
            if (!$index2->hasColumnAtPosition($indexColumn, $i)) {
                return true;
            }
        }

        // Check for new index fields in $index2
        $index2Columns = $index2->getColumns();
        for($i = 0; $i < count($index2Columns); $i++) {
            $indexColumn = $index2Columns[$i];
            if (!$index1->hasColumnAtPosition($indexColumn, $i)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param Schema $fromSchema
     * @param Schema $toSchema
     * @param AbstractSchemaManager $sm
     * @return array
     */
    public function toSql(Schema $fromSchema, Schema $toSchema, AbstractSchemaManager $sm)
    {
        $diffSchema = $this->compare($fromSchema, $toSchema);


    }
}