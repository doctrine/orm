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
 * and is licensed under the MIT license. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace Doctrine\ORM\Mapping;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\DBAL\Platforms\AbstractPlatform;

/**
 * A set of rules for determining the physical column, alias and table quotes
 *
 * @since   2.3
 * @author  Fabio B. Silva <fabio.bat.silva@gmail.com>
 */
class DefaultQuoteStrategy implements QuoteStrategy
{
    /**
     * {@inheritdoc}
     */
    public function getColumnName($fieldName, ClassMetadata $class, AbstractPlatform $platform)
    {
        return isset($class->fieldMappings[$fieldName]['quoted'])
            ? $platform->quoteIdentifier($class->fieldMappings[$fieldName]['columnName'])
            : $class->fieldMappings[$fieldName]['columnName'];
    }

    /**
     * {@inheritdoc}
     */
    public function getTableName(ClassMetadata $class, AbstractPlatform $platform)
    {
        return isset($class->table['quoted']) 
            ? $platform->quoteIdentifier($class->table['name'])
            : $class->table['name'];
    }

    /**
     * {@inheritdoc}
     */
    public function getSequenceName(array $definition, ClassMetadata $class, AbstractPlatform $platform)
    {
        return isset($definition['quoted'])
            ? $platform->quoteIdentifier($definition['sequenceName'])
            : $definition['sequenceName'];
    }

    /**
     * {@inheritdoc}
     */
    public function getJoinColumnName(array $joinColumn, ClassMetadata $class, AbstractPlatform $platform)
    {
        return isset($joinColumn['quoted'])
            ? $platform->quoteIdentifier($joinColumn['name'])
            : $joinColumn['name'];
    }

    /**
     * {@inheritdoc}
     */
    public function getReferencedJoinColumnName(array $joinColumn, ClassMetadata $class, AbstractPlatform $platform)
    {
        return isset($joinColumn['quoted'])
            ? $platform->quoteIdentifier($joinColumn['referencedColumnName'])
            : $joinColumn['referencedColumnName'];
    }

    /**
     * {@inheritdoc}
     */
    public function getJoinTableName(array $association, ClassMetadata $class, AbstractPlatform $platform)
    {
        return isset($association['joinTable']['quoted'])
            ? $platform->quoteIdentifier($association['joinTable']['name'])
            : $association['joinTable']['name'];
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentifierColumnNames(ClassMetadata $class, AbstractPlatform $platform)
    {
        $quotedColumnNames = array();

        foreach ($class->identifier as $fieldName) {
            if (isset($class->fieldMappings[$fieldName])) {
                $quotedColumnNames[] = $this->getColumnName($fieldName, $class, $platform);

                continue;
            }

            // Association defined as Id field
            $joinColumns            = $class->associationMappings[$fieldName]['joinColumns'];
            $assocQuotedColumnNames = array_map(
                function ($joinColumn) use ($platform)
                {
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
     * {@inheritdoc}
     */
    public function getColumnAlias($columnName, $counter, AbstractPlatform $platform, ClassMetadata $class = null)
    {
        // Trim the column alias to the maximum identifier length of the platform.
        // If the alias is to long, characters are cut off from the beginning.
        // And strip non alphanumeric characters
        $columnName = $columnName . $counter;
        $columnName = substr($columnName, -$platform->getMaxIdentifierLength());
        $columnName = preg_replace('/[^A-Za-z0-9_]/', '', $columnName);

        return $platform->getSQLResultCasing($columnName);
    }
}
