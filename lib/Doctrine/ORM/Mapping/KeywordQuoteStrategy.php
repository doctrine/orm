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

use Doctrine\DBAL\Platforms\AbstractPlatform;

/**
 * A set of rules for determining the physical column, alias and table quotes
 *
 * @since   2.6
 * @author  Maximilian Ruta <mr@xtain.net>
 */
class KeywordQuoteStrategy implements QuoteStrategy
{
   /**
     * @param AbstractPlatform $platform
     * @param string $name
     * @param bool $force
     *
     * @return string
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getQuotedName(AbstractPlatform $platform, $name, $force = false)
    {
        $keywords = $platform->getReservedKeywordsList();
        $parts = explode(".", $name);
        foreach ($parts as $k => $v) {
            $parts[$k] = ($force || $keywords->isKeyword($v)) ? $platform->quoteIdentifier($v) : $v;
        }

        return implode(".", $parts);
    }

    /**
     * {@inheritdoc}
     */
    public function getColumnName($fieldName, ClassMetadata $class, AbstractPlatform $platform)
    {
        return $this->getQuotedName(
            $platform,
            $class->fieldMappings[$fieldName]['columnName'],
            isset($class->fieldMappings[$fieldName]['quoted'])
        );
    }

    /**
     * {@inheritdoc}
     *
     * @todo Table names should be computed in DBAL depending on the platform
     */
    public function getTableName(ClassMetadata $class, AbstractPlatform $platform)
    {
        $tableName = $class->table['name'];

        if ( ! empty($class->table['schema'])) {
            $tableName = $class->table['schema'] . '.' . $class->table['name'];

            if ( ! $platform->supportsSchemas() && $platform->canEmulateSchemas()) {
                $tableName = $class->table['schema'] . '__' . $class->table['name'];
            }
        }

        return $this->getQuotedName(
            $platform,
            $tableName,
            isset($class->table['quoted'])
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getSequenceName(array $definition, ClassMetadata $class, AbstractPlatform $platform)
    {
        return $this->getQuotedName(
            $platform,
            $definition['sequenceName'],
            isset($definition['quoted'])
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getJoinColumnName(array $joinColumn, ClassMetadata $class, AbstractPlatform $platform)
    {
        return $this->getQuotedName(
            $platform,
            $joinColumn['name'],
            isset($joinColumn['quoted'])
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getReferencedJoinColumnName(array $joinColumn, ClassMetadata $class, AbstractPlatform $platform)
    {
        return $this->getQuotedName(
            $platform,
            $joinColumn['referencedColumnName'],
            isset($joinColumn['quoted'])
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getJoinTableName(array $association, ClassMetadata $class, AbstractPlatform $platform)
    {
        $schema = '';

        if (isset($association['joinTable']['schema'])) {
            $schema = $association['joinTable']['schema'] . '.';
        }

        $tableName = $association['joinTable']['name'];

        $tableName = $this->getQuotedName(
            $platform,
            $tableName,
            isset($association['joinTable']['quoted'])
        );

        return $schema . $tableName;
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
            $self                   = $this;
            $joinColumns            = $class->associationMappings[$fieldName]['joinColumns'];
            $assocQuotedColumnNames = array_map(
                function ($joinColumn) use ($platform, $self)
                {
                    return $self->getQuotedName(
                        $platform,
                        $joinColumn['name'],
                        isset($joinColumn['quoted'])
                    );
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
        // 1 ) Concatenate column name and counter
        // 2 ) Trim the column alias to the maximum identifier length of the platform.
        //     If the alias is to long, characters are cut off from the beginning.
        // 3 ) Strip non alphanumeric characters
        // 4 ) Prefix with "_" if the result its numeric
        $columnName = $columnName . '_' . $counter;
        $columnName = substr($columnName, -$platform->getMaxIdentifierLength());
        $columnName = preg_replace('/[^A-Za-z0-9_]/', '', $columnName);
        $columnName = is_numeric($columnName) ? '_' . $columnName : $columnName;

        return $platform->getSQLResultCasing($columnName);
    }
}
