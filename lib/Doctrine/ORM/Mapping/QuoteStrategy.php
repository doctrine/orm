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
 * A set of rules for determining the column, alias and table quotes.
 *
 * @since   2.3
 * @author  Fabio B. Silva <fabio.bat.silva@gmail.com>
 */
interface QuoteStrategy
{
    /**
     * Gets the (possibly quoted) primary table name for safe use in an SQL statement.
     *
     * @param ClassMetadata    $class
     * @param AbstractPlatform $platform
     *
     * @return string
     */
    public function getTableName(ClassMetadata $class, AbstractPlatform $platform);

    /**
     * Gets the (possibly quoted) name of the join table.
     *
     * @param array            $association
     * @param ClassMetadata    $class
     * @param AbstractPlatform $platform
     *
     * @return string
     */
    public function getJoinTableName(array $association, ClassMetadata $class, AbstractPlatform $platform);

    /**
     * Gets the (possibly quoted) identifier column names for safe use in an SQL statement.
     *
     * @param ClassMetadata    $class
     * @param AbstractPlatform $platform
     *
     * @return array
     */
    public function getIdentifierColumnNames(ClassMetadata $class, AbstractPlatform $platform);

    /**
     * Gets the column alias.
     *
     * @param string             $columnName
     * @param integer            $counter
     * @param AbstractPlatform   $platform
     * @param ClassMetadata|null $class
     *
     * @return string
     */
    public function getColumnAlias($columnName, $counter, AbstractPlatform $platform, ClassMetadata $class = null);

}
