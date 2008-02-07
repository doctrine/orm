<?php 

class Doctrine_Mapper_SingleTable extends Doctrine_Mapper_Abstract
{
    
    /*public function addToWhere($componentAlias, array &$sqlWhereParts, Doctrine_Query $query)
    {
        $array = array();
        $componentParts = $query->getQueryComponent($componentAlias);
        $sqlTableAlias = $query->getSqlTableAlias($componentAlias);
        $array[$sqlTableAlias][] = $this->getDiscriminatorColumn();
        
        // apply inheritance maps
        $str = '';
        $c = array();

        $index = 0;
        foreach ($array as $tableAlias => $maps) {
            $a = array();

            // don't use table aliases if the query isn't a select query
            if ($query->getType() !== Doctrine_Query::SELECT) {
                $tableAlias = '';
            } else {
                $tableAlias .= '.';
            }

            foreach ($maps as $map) {
                $b = array();
                foreach ($map as $field => $value) {
                    $identifier = $this->_conn->quoteIdentifier($tableAlias . $field);

                    if ($index > 0) {
                        $b[] = '(' . $identifier . ' = ' . $this->_conn->quote($value)
                             . ' OR ' . $identifier . ' IS NULL)';
                    } else {
                        $b[] = $identifier . ' = ' . $this->_conn->quote($value);
                    }
                }

                if ( ! empty($b)) {
                    $a[] = implode(' AND ', $b);
                }
            }

            if ( ! empty($a)) {
                $c[] = implode(' AND ', $a);
            }
            $index++;
        }

        $str .= implode(' AND ', $c);

        return $str;
    }*/
}

