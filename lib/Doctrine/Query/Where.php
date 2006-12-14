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
        $where = trim($where);

        $e     = Doctrine_Query::sqlExplode($where);

        if(count($e) > 1) {
            $tmp   = $e[0].' '.$e[1];

            if(substr($tmp, 0, 6) == 'EXISTS')
                return $this->parseExists($where, true);
            elseif(substr($where, 0, 10) == 'NOT EXISTS')
                return $this->parseExists($where, false);
        }

        if(count($e) < 3) {
            $e = Doctrine_Query::sqlExplode($where, array('=', '<', '>', '!='));
        }
        $r = array_shift($e);

        $a = explode('.', $r);

        if(count($a) > 1) {
            $field     = array_pop($a);
            $count     = count($e);
            $slice     = array_slice($e, -1, 1);
            $value     = implode('', $slice);
            $operator  = trim(substr($where, strlen($r), -strlen($value)));

            $reference = implode('.', $a);
            $count     = count($a);



            $pos       = strpos($field, "(");

            if($pos !== false) {
                $func   = substr($field, 0, $pos);
                $value  = trim(substr($field, ($pos + 1), -1));

                $values = Doctrine_Query::sqlExplode($value, ',');

                $field      = array_pop($a);
                $reference  = implode('.', $a);
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
                $alias     = $this->query->getTableAlias($reference);
                $table     = $this->query->getTable($alias);
                // check if value is enumerated value
                $enumIndex = $table->enumIndex($field, trim($value, "'"));

                if(substr($value, 0, 1) == '(') {
                    // trim brackets
                    $trimmed   = Doctrine_Query::bracketTrim($value);

                    if(substr($trimmed, 0, 4) == 'FROM' || substr($trimmed, 0, 6) == 'SELECT') {
                        // subquery found
                        $q     = new Doctrine_Query();
                        $value = '(' . $q->parseQuery($trimmed)->getQuery() . ')';
                    } elseif(substr($trimmed, 0, 4) == 'SQL:') {
                        $value = '(' . substr($trimmed, 4) . ')';
                    } else {
                        // simple in expression found
                        $e     = Doctrine_Query::sqlExplode($trimmed, ',');
                        
                        $value = array();
                        foreach($e as $part) {
                            $index   = $table->enumIndex($field, trim($part, "'"));
                            if($index !== false)
                                $value[] = $index;
                            else
                                $value[] = $this->parseLiteralValue($part);
                        }
                        $value = '(' . implode(', ', $value) . ')';
                    }
                } else {
                    if($enumIndex !== false)
                        $value = $enumIndex;
                    else
                        $value = $this->parseLiteralValue($value);
                }


                switch($operator) {
                    case '<':
                    case '>':
                    case '=':
                    case '!=':
                        if($enumIndex !== false)
                            $value  = $enumIndex;
                    default:
                        $where      = $alias.'.'.$field.' '.$operator.' '.$value;
                }
            }
        }
        return $where;
    }
    /**
     * parses a literal value and returns the parsed value
     * 
     * boolean literals are parsed to integers
     * components are parsed to associated table aliases
     *
     * @param string $value         literal value to be parsed
     * @return string
     */
    public function parseLiteralValue($value) {
        // check that value isn't a string
        if(strpos($value, '\'') === false) {
                        
            // parse booleans
            if($value == 'true')
                $value = 1;
            elseif($value == 'false')
                $value = 0;

            $a = explode('.', $value);

            if(count($a) > 1) {
            // either a float or a component..
    
                if( ! is_numeric($a[0])) {
                    // a component found
                    $value = $this->query->getTableAlias($a[0]). '.' . $a[1];
                }
            }
        } else {
            // string literal found
        }

        return $value;
    }
    /**
     * parses an EXISTS expression
     *
     * @param string $where         query where part to be parsed
     * @param boolean $negation     whether or not to use the NOT keyword
     * @return string
     */
    public function parseExists($where, $negation) {
        $operator = ($negation) ? 'EXISTS' : 'NOT EXISTS';

        $pos = strpos($where, '(');
        
        if($pos == false)
            throw new Doctrine_Query_Exception("Unknown expression, expected '('");

        $sub = Doctrine_Query::bracketTrim(substr($where, $pos));

        return $operator . ' ('.$this->query->createSubquery()->parseQuery($sub, false)->getQuery() . ')';
    }
    /**
     * getOperator
     *
     * @param string $func
     * @return string
     */
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
        return ( ! empty($this->parts))?implode(' AND ', $this->parts):'';
    }
}

