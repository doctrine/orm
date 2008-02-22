<?php
/*
 *  $Id: Db.php 3155 2007-11-14 13:13:23Z jwage $
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
 * <http://www.phpdoctrine.org>.
 */

/**
 * @package     Doctrine
 * @subpackage  Log
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @author      Jonathan H. Wage <jwage@mac.com>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.phpdoctrine.org
 * @since       1.0
 * @version     $Revision: 3155 $
 */
class Doctrine_Log_Writer_Db extends Doctrine_Log_Writer_Abstract
{
    /**
     * Doctrine_Table instance
     *
     * @var string
     */
    private $_table;

    /**
     * Relates database columns names to log data field keys.
     *
     * @var null|array
     */
    private $_columnMap;

    /**
     * Class constructor
     *
     * @param Doctrine_Db_Adapter $db   Database adapter instance
     * @param string $table         Log table in database
     * @param array $columnMap
     */
    public function __construct($table, $columnMap = null)
    {
        if (!$table instanceof Doctrine_Table) {
            $table = Doctrine::getTable($table);
        }
        
        $this->_table = $table;
        $this->_columnMap = $columnMap;
    }

    /**
     * Formatting is not possible on this writer
     */
    public function setFormatter($formatter)
    {
        throw new Doctrine_Log_Exception(get_class() . ' does not support formatting');
    }

    /**
     * Remove reference to database adapter
     *
     * @return void
     */
    public function shutdown()
    {
        $this->_table = null;
    }

    /**
     * Write a message to the log.
     *
     * @param  array  $event  event data
     * @return void
     */
    protected function _write($event)
    {
        if ($this->_table === null) {
            throw new Doctrine_Log_Exception('Database adapter instance has been removed by shutdown');
        }

        if ($this->_columnMap === null) {
            $dataToInsert = $event;
        } else {
            $dataToInsert = array();
            foreach ($this->_columnMap as $columnName => $fieldKey) {
                $dataToInsert[$columnName] = $event[$fieldKey];
            }
        }
        
        $record = $this->_table->create($dataToInsert);
        $record->save();
    }
}