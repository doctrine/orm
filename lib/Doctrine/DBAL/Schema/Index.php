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

use Doctrine\DBAL\Schema\Visitor\Visitor;

class Index extends AbstractAsset implements Constraint
{
    /**
     * @var array
     */
    protected $_columns;

    /**
     * @var bool
     */
    protected $_isUnique = false;

    /**
     * @var bool
     */
    protected $_isPrimary = false;

    /**
     * @param string $indexName
     * @param array $column
     * @param bool $isUnique
     * @param bool $isPrimary
     */
    public function __construct($indexName, array $columns, $isUnique=false, $isPrimary=false)
    {
        $isUnique = ($isPrimary)?true:$isUnique;

        $this->_setName($indexName);
        $this->_isUnique = $isUnique;
        $this->_isPrimary = $isPrimary;

        foreach($columns AS $column) {
            $this->_addColumn($column);
        }
    }

    /**
     * @param string $column
     */
    protected function _addColumn($column)
    {
        if(is_string($column)) {
            $this->_columns[] = $column;
        } else {
            throw new \InvalidArgumentException("Expecting a string as Index Column");
        }
    }

    /**
     * @return array
     */
    public function getColumns()
    {
        return $this->_columns;
    }

    /**
     * @return bool
     */
    public function isUnique()
    {
        return $this->_isUnique;
    }

    /**
     * @return bool
     */
    public function isPrimary()
    {
        return $this->_isPrimary;
    }

    /**
     * @param  string $columnName
     * @param  int $pos
     * @return bool
     */
    public function hasColumnAtPosition($columnName, $pos=0)
    {
        $columnName = strtolower($columnName);
        $indexColumns = \array_map('strtolower', $this->getColumns());
        return \array_search($columnName, $indexColumns) === $pos;
    }
}