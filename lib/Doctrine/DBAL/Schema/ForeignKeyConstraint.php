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

class ForeignKeyConstraint extends AbstractAsset implements Constraint
{
    /**
     * @var Table
     */
    protected $_localTable;

    /**
     * @var array
     */
    protected $_localColumnNames;

    /**
     * @var string
     */
    protected $_foreignTableName;

    /**
     * @var array
     */
    protected $_foreignColumnNames;

    /**
     * @var string
     */
    protected $_cascade = '';

    /**
     * @var array
     */
    protected $_options;

    /**
     *
     * @param array $localColumnNames
     * @param string $foreignTableName
     * @param array $foreignColumnNames
     * @param string $cascade
     * @param string|null $name
     */
    public function __construct(array $localColumnNames, $foreignTableName, array $foreignColumnNames, $name=null, array $options=array())
    {
        $this->_setName($name);
        $this->_localColumnNames = $localColumnNames;
        $this->_foreignTableName = $foreignTableName;
        $this->_foreignColumnNames = $foreignColumnNames;
        $this->_options = $options;
    }

    /**
     * @return string
     */
    public function getLocalTableName()
    {
        return $this->_localTable->getName();
    }

    /**
     * @param Table $table
     */
    public function setLocalTable(Table $table)
    {
        $this->_localTable = $table;
    }

    /**
     * @return array
     */
    public function getLocalColumns()
    {
        return $this->_localColumnNames;
    }

    public function getColumns()
    {
        return $this->_localColumnNames;
    }

    /**
     * @return string
     */
    public function getForeignTableName()
    {
        return $this->_foreignTableName;
    }

    /**
     * @return array
     */
    public function getForeignColumns()
    {
        return $this->_foreignColumnNames;
    }

    public function hasOption($name)
    {
        return isset($this->_options[$name]);
    }

    public function getOption($name)
    {
        return $this->_options[$name];
    }

    /**
     * Foreign Key onUpdate status
     *
     * @return string|null
     */
    public function onUpdate()
    {
        return $this->_onEvent('onUpdate');
    }

    /**
     * Foreign Key onDelete status
     *
     * @return string|null
     */
    public function onDelete()
    {
        return $this->_onEvent('onDelete');
    }

    /**
     * @param  string $event
     * @return string|null
     */
    private function _onEvent($event)
    {
        if (isset($this->_options[$event])) {
            $onEvent = strtoupper($this->_options[$event]);
            if (!in_array($onEvent, array('NO ACTION', 'RESTRICT'))) {
                return $onEvent;
            }
        }
        return false;
    }
}
