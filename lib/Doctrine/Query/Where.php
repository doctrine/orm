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
    public function load($where) {

        $e = Doctrine_Query::sqlExplode($where);
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
                $func   = substr($field, 0, $pos);
                $value  = trim(substr($field, ($pos + 1), -1));

                $values = Doctrine_Query::sqlExplode($value, ',');

                $field      = array_pop($a);
                $reference  = implode(".",$a);
                $table      = $this->query->load($reference, false);
                array_pop($a);
                $reference2 = implode('.', $a);
                $alias      = $this->query->getTableAlias($reference2);

                $stack      = $this->query->getRelationStack();
                $relation   = end($stack);
                
                $stack      = $this->query->getTableStack();

                switch($func) {
                    case 'contains':
                    case 'regexp':
                    case 'like':
                        $operator = $this->getOperator($func);

                        if(empty($relation))
                            throw new Doctrine_Query_Exception('DQL functions contains/regexp/like can only be used for fields of related components');
                        
                        $where = array();
                        foreach($values as $value) {
                            $where[] = $alias.'.'.$relation->getLocal().
                              ' IN (SELECT '.$relation->getForeign().
                              ' FROM '.$relation->getTable()->getTableName().' WHERE '.$field.$operator.$value.')';
                        }
                        $where = implode(' AND ', $where);
                    break;
                    default:
                        throw new Doctrine_Query_Exception('Unknown DQL function: '.$func);
                }
            } else {
                $table     = $this->query->load($reference, false);
                $enumIndex = $table->enumIndex($field, trim($value,"'"));
                $alias     = $this->query->getTableAlias($reference);
                $table     = $this->query->getTable($alias);


                if($value == 'true')
                    $value = 1;
                elseif($value == 'false')
                    $value = 0;
                elseif(substr($value,0,5) == '(FROM') {
                    $sub   = Doctrine_Query::bracketTrim($value);
                    $q     = new Doctrine_Query();
                    $value = '(' . $q->parseQuery($sub)->getQuery() . ')';
                }

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
        } 
        return $where;
    }

    public function getOperator($func) {
        switch($func) {
            case 'contains':
                $operator = ' = ';
            break;
            case 'regexp':
                $operator = $this->query->getConnection()->getRegexpOperator();
            break;
            case 'like':
                $operator = ' LIKE ';
            break;
        }
        return $operator;
    }

    public function __toString() {
        return ( ! empty($this->parts))?implode(" AND ", $this->parts):'';
    }
}

