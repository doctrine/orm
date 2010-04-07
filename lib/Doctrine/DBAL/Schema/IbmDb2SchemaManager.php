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
 * IBM Db2 Schema Manager
 *
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.doctrine-project.com
 * @since       1.0
 * @version     $Revision$
 * @author      Benjamin Eberlei <kontakt@beberlei.de>
 */
class IbmDb2SchemaManager extends AbstractSchemaManager
{
    /**
     * Get Table Column Definition
     *
     * @param array $tableColumn
     * @return Column
     */
    protected function _getPortableTableColumnDefinition($tableColumn)
    {
        
    }

    protected function _getPortableTableForeignKeyDefinition($tableForeignKey)
    {
        $tableForeignKey = array_change_key_case($tableForeignKey, CASE_LOWER);

        $tableForeignKey['delete_rule'] = $this->_getPortableForeignKeyRuleDef($tableForeignKey['delete_rule']);
        $tableForeignKey['update_rule'] = $this->_getPortableForeignKeyRuleDef($tableForeignKey['update_rule']);

        return new ForeignKeyConstraint(
            (array)$tableForeignKey['pkcolnames'],
            $tableForeignKey['referenced_table_name'],
            (array)$tableForeignKey['fkcolnames'],
            $tableForeignKey['relname'],
            array(
                'onUpdate' => $tableForeignKey['update_rule'],
                'onDelete' => $tableForeignKey['delete_rule'],
            )
        );
    }

    protected function _getPortableForeignKeyRuleDef($def)
    {
        if ($def == "C") {
            return "CASCADE";
        } else if ($def == "N") {
            return "SET NULL";
        }
        return null;
    }

    protected function _getPortableViewDefinition($view)
    {
        $view = array_change_key_case($view, \CASE_LOWER);
        $pos = strpos($view['text'], ' AS ');
        $sql = substr($view['text'], $pos+4);

        return new View($view['name'], $sql);
    }
}