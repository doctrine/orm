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
 * The abstract asset allows to reset the name of all assets without publishing this to the public userland.
 *
 * This encapsulation hack is necessary to keep a consistent state of the database schema. Say we have a list of tables
 * array($tableName => Table($tableName)); if you want to rename the table, you have to make sure
 *
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link    www.doctrine-project.org
 * @since   2.0
 * @version $Revision$
 * @author  Benjamin Eberlei <kontakt@beberlei.de>
 */
abstract class AbstractAsset
{
    /**
     * @var string
     */
    protected $_name;

    /**
     * Set name of this asset
     *
     * @param string $name
     */
    protected function _setName($name)
    {
        $this->_name = $name;
    }

    /**
     * Return name of this schema asset.
     * 
     * @return string
     */
    public function getName()
    {
        return $this->_name;
    }

    /**
     * Generate an identifier from a list of column names obeying a certain string length.
     *
     * This is especially important for Oracle, since it does not allow identifiers larger than 30 chars,
     * however building idents automatically for foreign keys, composite keys or such can easily create
     * very long names.
     *
     * @param  array $columnNames
     * @param  string $postfix
     * @param  int $maxSize
     * @return string
     */
    protected function _generateIdentifierName($columnNames, $postfix='', $maxSize=30)
    {
        $columnCount = count($columnNames);
        $postfixLen = strlen($postfix);
        $parts = array_map(function($columnName) use($columnCount, $postfixLen, $maxSize) {
            return substr($columnName, -floor(($maxSize-$postfixLen)/$columnCount - 1));
        }, $columnNames);
        $parts[] = $postfix;

        return trim(implode("_", $parts), '_');
    }
}