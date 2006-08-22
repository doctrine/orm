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
 * <http://www.phpdoctrine.com>.
 */
require_once("Hydrate.php");
/**
 * Doctrine_RawSql
 *
 * @package     Doctrine ORM
 * @url         www.phpdoctrine.com
 * @license     LGPL
 */
class Doctrine_RawSql extends Doctrine_Hydrate {
    /**
     * @var array $fields
     */
    private $fields;
    /**
     * __call
     * method overloader
     *
     * @param string $name
     * @param array $args
     * @return Doctrine_RawSql
     */
    public function __call($name, $args) {
        if( ! isset($this->parts[$name]))
            throw new Doctrine_Exception("Unknown overload method");

        if($name == 'select') {
            preg_match_all('/{([^}{]*)}/U', $args[0], $m);

            $this->fields = $m[1];
            $this->parts["select"] = array();
        } else
            $this->parts[$name][] = $args[0];

        return $this;
    }
    /**
     * get
     */
    public function get($name) {
        if( ! isset($this->parts[$name])) 
            throw new Doctrine_Exception('Unknown query part '.$name);
            
        return $this->parts[$name];
    }
    /**
     * parseQuery
     *
     * @param string $query
     * @return Doctrine_RawSql
     */
    public function parseQuery($query) {
        preg_match_all('/{([^}{]*)}/U', $query, $m);

        $this->fields = $m[1];
        $this->clear();

        $e = Doctrine_Query::bracketExplode($query,' ');

        foreach($e as $k => $part):
            $low = strtolower($part);
            switch(strtolower($part)):
                case "select":
                case "from":
                case "where":
                case "limit":
                case "offset":
                case "having":
                    $p = $low;
                    if( ! isset($parts[$low]))
                        $parts[$low] = array();

                break;
                case "order":
                case "group":
                    $i = ($k + 1);
                    if(isset($e[$i]) && strtolower($e[$i]) === "by") {
                        $p = $low;
                        $p .= "by";
                        $parts[$low."by"] = array();

                    } else
                        $parts[$p][] = $part;
                break;
                case "by":
                    continue;
                default:
                    if( ! isset($parts[$p][0])) 
                        $parts[$p][0] = $part;
                    else
                        $parts[$p][0] .= ' '.$part;
            endswitch;
        endforeach;

        $this->parts = $parts;
        $this->parts["select"] = array();
        
        return $this;
    }
    /**
     * getQuery
     *
     *
     * @return string
     */
    public function getQuery() {


        foreach($this->fields as $field) {
            $e = explode(".", $field);
            if( ! isset($e[1]))
                throw new Doctrine_Exception("All selected fields in Sql query must be in format tableAlias.fieldName");

            if( ! isset($this->tables[$e[0]])) {
                try {
                    $this->addComponent($e[0], ucwords($e[0]));
                } catch(Doctrine_Exception $exception) {
                    throw new Doctrine_Exception("The associated component for table alias $e[0] couldn't be found.");
                }
            }

            if($e[1] == '*') {
                foreach($this->tables[$e[0]]->getColumnNames() as $name) {
                    $field = $e[0].".".$name;
                    $this->parts["select"][$field] = $field." AS ".$e[0]."__".$name;
                }
            } else {
                $field = $e[0].".".$e[1];
                $this->parts["select"][$field] = $field." AS ".$e[0]."__".$e[1];
            }
        }

        // force-add all primary key fields

        foreach($this->tableAliases as $alias) {
            foreach($this->tables[$alias]->getPrimaryKeys() as $key) {
                $field = $alias.".".$key;
                if( ! isset($this->parts["select"][$field]))
                    $this->parts["select"][$field] = $field." AS ".$alias."__".$key;
            }
        }

        $q = "SELECT ".implode(', ', $this->parts['select']);

        $string = $this->applyInheritance();
        if( ! empty($string))
            $this->parts["where"][] = $string;

        $copy = $this->parts;
        unset($copy['select']);

        $q .= ( ! empty($this->parts['from']))?" FROM ".implode(" ",$this->parts["from"]):'';
        $q .= ( ! empty($this->parts['where']))?" WHERE ".implode(" AND ",$this->parts["where"]):'';
        $q .= ( ! empty($this->parts['groupby']))?" GROUP BY ".implode(", ",$this->parts["groupby"]):'';
        $q .= ( ! empty($this->parts['having']))?" HAVING ".implode(" ",$this->parts["having"]):'';
        $q .= ( ! empty($this->parts['orderby']))?" ORDER BY ".implode(" ",$this->parts["orderby"]):'';

        if( ! empty($string))
            array_pop($this->parts['where']);

        return $q;
    }
    /**
     * getFields
     *
     * @return array
     */
    public function getFields() {
        return $this->fields;
    }
    /**
     * addComponent
     *
     * @param string $tableAlias
     * @param string $componentName
     * @return Doctrine_RawSql
     */
    public function addComponent($tableAlias, $componentName) {
        $e = explode(".", $componentName);

        $currPath = '';

        foreach($e as $k => $component) {
            $currPath .= '.'.$component;
            if($k == 0)
                $currPath = substr($currPath,1);

            if(isset($this->tableAliases[$currPath]))
                $alias = $this->tableAliases[$currPath];
            else
                $alias = $tableAlias;

            $table = $this->connection->getTable($component);
            $this->tables[$alias]           = $table;
            $this->fetchModes[$alias]       = Doctrine::FETCH_IMMEDIATE;
            $this->tableAliases[$currPath]  = $alias;

            if($k !== 0)
                $this->joins[$alias]        = $prevAlias;

            $prevAlias = $alias;
            $prevPath  = $currPath;
        }
        
        return $this;
    }

}
?>
