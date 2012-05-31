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

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\DBAL\Platforms\AbstractPlatform;

/**
 * A set of rules for determining the physical column, alias and table quotes
 *
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link    www.doctrine-project.org
 * @since   2.4
 * @author  Fabio B. Silva <fabio.bat.silva@gmail.com>
 */
abstract class QuoteStrategy
{
    /**
     * @var \Doctrine\DBAL\Platforms\AbstractPlatform
     */
    protected $platform;

    /**
     * @param AbstractPlatform $platform
     */
    public function __construct(AbstractPlatform $platform)
    {
        $this->platform = $platform;
    }

    /**
     * Checks if the given identifier is quoted
     *
     * @param   string $identifier
     * @return  string
     */
    abstract public function isQuotedIdentifier($identifier);

    /**
     * Gets the uquoted column name.
     *
     * @param   string $identifier
     * @return  string
     */
    abstract public function getUnquotedIdentifier($identifier);

    /**
     * Gets the (possibly quoted) column name for safe use in an SQL statement.
     *
     * @param   string $fieldName
     * @param   ClassMetadata $class
     * @return  string
     */
    abstract public function getColumnName($fieldName, ClassMetadata $class);

    /**
     * Gets the (possibly quoted) primary table name for safe use in an SQL statement.
     *
     * @param   ClassMetadata $class
     * @return  string
     */
    abstract public function getTableName(ClassMetadata $class);

    /**
     * Gets the (possibly quoted) name of the join table.
     *
     * @param   ClassMetadata $class
     * @return  string
     */
    abstract public function getJoinTableName(array $association, ClassMetadata $class);

    /**
     * Gets the (possibly quoted) identifier column names for safe use in an SQL statement.
     *
     * @param   ClassMetadata $class
     * @return  array
     */
    abstract public function getIdentifierColumnNames(ClassMetadata $class);

    /**
     * Gets the column alias.
     *
     * @param   string  $columnName
     * @param   integer $counter
     * @param   ClassMetadata $class
     * @return  string
     */
    abstract public function getColumnAlias($columnName, $counter, ClassMetadata $class = null);

}