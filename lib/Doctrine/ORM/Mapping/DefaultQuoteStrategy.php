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

namespace Doctrine\ORM\Mapping;


/**
 * A set of rules for determining the physical column, alias and table quotes
 *
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link    www.doctrine-project.org
 * @since   2.4
 * @author  Fabio B. Silva <fabio.bat.silva@gmail.com>
 */
class DefaultQuoteStrategy extends QuoteStrategy
{

    /**
     * Checks if the given identifier is quoted
     *
     * @param   string $identifier
     * @return  string
     */
    public function isQuotedIdentifier($identifier)
    {
        return strlen($identifier) > 0 && $identifier[0] === '`';
    }

    /**
     * Gets the uquoted column name.
     * 
     * @param   string $identifier
     * @return  string
     */
    public function getUnquotedIdentifier($identifier)
    {
        return trim($identifier, '`');
    } 

    /**
     * Gets the (possibly quoted) column name for safe use in an SQL statement.
     * 
     * @param   string $fieldName
     * @param   ClassMetadata $class
     * @return  string
     */
    public function getColumnName($fieldName, ClassMetadata $class)
    {
        return isset($class->fieldMappings[$fieldName]['quoted'])
            ? $this->platform->quoteIdentifier($class->fieldMappings[$fieldName]['columnName'])
            : $class->fieldMappings[$fieldName]['columnName'];
    }

    /**
     * Gets the (possibly quoted) primary table name for safe use in an SQL statement.
     *
     * @param   ClassMetadata $class
     * @return  string
     */
    public function getTableName(ClassMetadata $class)
    {
        return isset($class->table['quoted']) 
                ? $this->platform->quoteIdentifier($class->table['name'])
                : $class->table['name'];
    }

    /**
     * Gets the (possibly quoted) name of the join table.
     *
     * @param   ClassMetadata $class
     * @return  string
     */
    public function getJoinTableName($relation, ClassMetadata $class)
    {
        $assoc = $class->associationMappings[$relation];
        return isset($assoc['joinTable']['quoted']) 
            ? $this->platform->quoteIdentifier($assoc['joinTable']['name'])
            : $assoc['joinTable']['name'];
    }

    /**
     * Gets the (possibly quoted) identifier column names for safe use in an SQL statement.
     *
     * @param   ClassMetadata $class
     * @return  array
     */
    public function getIdentifierColumnNames(ClassMetadata $class)
    {
        $quotedColumnNames = array();

        foreach ($class->identifier as $fieldName) {
            if (isset($class->fieldMappings[$fieldName])) {
                $quotedColumnNames[] = $this->getColumnName($fieldName, $class);

                continue;
            }

            // Association defined as Id field
            $platform               = $this->platform;
            $joinColumns            = $class->associationMappings[$fieldName]['joinColumns'];
            $assocQuotedColumnNames = array_map(
                function ($joinColumn) use ($platform) {
                    return isset($joinColumn['quoted'])
                        ? $platform->quoteIdentifier($joinColumn['name'])
                        : $joinColumn['name'];
                },
                $joinColumns
            );

            $quotedColumnNames = array_merge($quotedColumnNames, $assocQuotedColumnNames);
        }

        return $quotedColumnNames;
    }

    /**
     * Gets the column alias.
     *
     * @param   string  $columnName
     * @param   integer $counter
     * @param   ClassMetadata $class
     * @return  string
     */
    public function getColumnAlias($columnName, $counter, ClassMetadata $class = null)
    {
        // Trim the column alias to the maximum identifier length of the platform.
        // If the alias is to long, characters are cut off from the beginning.
        // And strip non alphanumeric characters
        $columnName = $columnName . $counter;
        $columnName = substr($columnName, -$this->platform->getMaxIdentifierLength());
        $columnName = preg_replace('/[^A-Za-z0-9_]/', '', $columnName);

        return $this->platform->getSQLResultCasing($columnName);
    }

}