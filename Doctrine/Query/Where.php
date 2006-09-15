<?php
require_once("Condition.php");

class Doctrine_Query_Where extends Doctrine_Query_Condition {
    /**
     * load
     * returns the parsed query part
     *
     * @param string $where
     * @return string
     */
    final public function load($where) {

        $e = explode(" ",$where);
        $r = array_shift($e);
        $a = explode(".",$r);


        if(count($a) > 1) {
            $field     = array_pop($a);
            $count     = count($e);
            $slice     = array_slice($e, 0, ($count - 1));
            $operator  = implode(' ', $slice);

            $slice     = array_slice($e, -1, 1);
            $value     = implode('', $slice);

            $reference = implode(".",$a);
            $count     = count($a);



            $pos       = strpos($field, "(");

            if($pos !== false) {
                $func  = substr($field, 0, $pos);
                $value = substr($field, ($pos + 1), -1);
                $field = array_pop($a);
                $reference = implode(".",$a);
                $table     = $this->query->load($reference, false);
                array_pop($a);
                $reference2 = implode('.', $a);
                $alias     = $this->query->getTableAlias($reference2);

                $stack      = $this->query->getRelationStack();
                $relation   = end($stack);
                
                $stack      = $this->query->getTableStack();

                switch($func) {
                    case 'contains':
                        $operator = ' = ';
                    case 'regexp':
                        $operator = ' RLIKE ';
                    case 'like':
                        if(empty($relation))
                            throw new Doctrine_Query_Exception('DQL function contains can only be used for fields of related components');

                        $where = $alias.'.'.$relation->getLocal().
                              ' IN (SELECT '.$relation->getForeign().
                              ' FROM '.$relation->getTable()->getTableName().' WHERE '.$field.' = '.$value.')';
                    break;
                    default:
                        throw new Doctrine_Query_Exception('Unknown DQL function: '.$func);
                }
            } else {
                $table     = $this->query->load($reference, false);
                $enumIndex = $table->enumIndex($field, trim($value,"'"));
                $alias     = $this->query->getTableAlias($reference);
                $table     = $this->query->getTable($alias);

                switch($operator) {
                    case '<':
                    case '>':
                    case '=':
                        if($enumIndex !== false)
                            $value  = $enumIndex;

                        $where      = $alias.'.'.$field.' '.$operator.' '.$value;
                    break;
                    default:
                        $where      = $this->query->getTableAlias($reference).'.'.$field.' '.$operator.' '.$value;
                }
            }
        } else 
            throw new Doctrine_Query_Exception('Unknown component path. The correct format should be component1.component2 ... componentN.field');
        return $where;
    }

    public function __toString() {
        return ( ! empty($this->parts))?implode(" AND ", $this->parts):'';
    }
}

