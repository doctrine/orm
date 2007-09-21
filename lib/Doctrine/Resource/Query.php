<?php
class Doctrine_Resource_Query extends Doctrine_Resource
{
    public $config = array();
    public $parts = array();
    public $dql = null;
    public $defaultFormat = 'xml';
    
    public function __construct($config)
    {
        $this->config = $config;
    }
    
    public function query($dql, $params = array())
    {
        $this->dql = $dql;
        
        return $this->execute($params);
    }
    
    public function execute($params = array())
    {
        $request = array();
        $request['dql'] = $this->getDql();
        $request['params'] = $params;
        $request['format'] = $this->getFormat();
        $request['type'] = 'query';
        
        $response = self::request($this->config['url'], $request);
        
        return $this->parseResponse($response);
    }
    
    public function getDql()
    {
        if (!$this->dql && !empty($this->parts)) {
            $q = '';
            $q .= ( ! empty($this->parts['select']))?  'SELECT '    . implode(', ', $this->parts['select']) : '';
            $q .= ( ! empty($this->parts['from']))?    ' FROM '     . implode(' ', $this->parts['from']) : '';
            $q .= ( ! empty($this->parts['where']))?   ' WHERE '    . implode(' AND ', $this->parts['where']) : '';
            $q .= ( ! empty($this->parts['groupby']))? ' GROUP BY ' . implode(', ', $this->parts['groupby']) : '';
            $q .= ( ! empty($this->parts['having']))?  ' HAVING '   . implode(' AND ', $this->parts['having']) : '';
            $q .= ( ! empty($this->parts['orderby']))? ' ORDER BY ' . implode(', ', $this->parts['orderby']) : '';
            $q .= ( ! empty($this->parts['limit']))?   ' LIMIT '    . implode(' ', $this->parts['limit']) : '';
            $q .= ( ! empty($this->parts['offset']))?  ' OFFSET '   . implode(' ', $this->parts['offset']) : '';
            
            return $q;
        } else {
            return $this->dql;
        }
    }
    
    public function parseResponse($response)
    {
        $array = Doctrine_Parser::load($response, $this->getFormat());
        
        $hydrated = $this->hydrate($array);
        
        return $hydrated;    
    }
    
    public function buildUrl($array)
    {
        $url = '';
        
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $url .= $this->buildUrl($value);
            } else {
                $url .= $key.'='.$value.'&';
            }
        }
        
        return $url;
    }
    
    public function hydrate(array $array, $passedKey = null)
    {
        $model = $passedKey ? $passedKey:$this->getModel();
        
        $collection = new Doctrine_Resource_Collection($model, $this->config);
        
        foreach ($array as $record) {
            $r = new Doctrine_Resource_Record($model, $this->config);
            
            foreach ($record as $key => $value) {
                if (is_array($value)) {
                    $r->data[$key] = $this->hydrate($value, $key);
                } else {
                    $r->data[$key] = $value;
                }
            }
        
            $collection->data[] = $r;
        }
        
        return $collection;
    }
    
    public function getModel()
    {
        $dql = $this->getDql();
        
        $e = explode('FROM ', $dql);
        $e = explode(' ', $e[1]);
        
        return $e[0];
    }
    
    public function setFormat($format)
    {
        $this->config['format'] = $format;
        
        return $this;
    }
    
    public function getFormat()
    {
        return isset($this->config['format']) ? $this->config['format']:$this->defaultFormat;
    }
    
    /**
     * addSelect
     * adds fields to the SELECT part of the query
     *
     * @param string $select        Query SELECT part
     * @return Doctrine_Query
     */
    public function addSelect($select)
    {
        return $this->parseQueryPart('select', $select, true);
    }
    /**
     * addFrom
     * adds fields to the FROM part of the query
     *
     * @param string $from        Query FROM part
     * @return Doctrine_Query
     */
    public function addFrom($from)
    {
        return $this->parseQueryPart('from', $from, true);
    }
    /**
     * addWhere
     * adds conditions to the WHERE part of the query
     *
     * @param string $where         Query WHERE part
     * @param mixed $params         an array of parameters or a simple scalar
     * @return Doctrine_Query
     */
    public function addWhere($where, $params = array())
    {
        if (is_array($params)) {
            $this->_params['where'] = array_merge($this->_params['where'], $params);
        } else {
            $this->_params['where'][] = $params;
        }
        return $this->parseQueryPart('where', $where, true);
    }
    /**
     * whereIn
     * adds IN condition to the query WHERE part
     *
     * @param string $expr
     * @param mixed $params         an array of parameters or a simple scalar
     * @return Doctrine_Query
     */
    public function whereIn($expr, $params = array())
    {
        $params = (array) $params;
        $a = array();
        foreach ($params as $k => $value) {
            if ($value instanceof Doctrine_Expression) {
                $value = $value->getSql();
                unset($params[$k]);
            } else {
                $value = '?';          
            }
            $a[] = $value;
        }

        $this->_params['where'] = array_merge($this->_params['where'], $params);

        $where = $expr . ' IN (' . implode(', ', $a) . ')';

        return $this->parseQueryPart('where', $where, true);
    }
    /**
     * addGroupBy
     * adds fields to the GROUP BY part of the query
     *
     * @param string $groupby       Query GROUP BY part
     * @return Doctrine_Query
     */
    public function addGroupBy($groupby)
    {
        return $this->parseQueryPart('groupby', $groupby, true);
    }
    /**
     * addHaving
     * adds conditions to the HAVING part of the query
     *
     * @param string $having        Query HAVING part
     * @param mixed $params         an array of parameters or a simple scalar
     * @return Doctrine_Query
     */
    public function addHaving($having, $params = array())
    {
        if (is_array($params)) {
            $this->_params['having'] = array_merge($this->_params['having'], $params);
        } else {
            $this->_params['having'][] = $params;
        }
        return $this->parseQueryPart('having', $having, true);
    }
    /**
     * addOrderBy
     * adds fields to the ORDER BY part of the query
     *
     * @param string $orderby       Query ORDER BY part
     * @return Doctrine_Query
     */
    public function addOrderBy($orderby)
    {
        return $this->parseQueryPart('orderby', $orderby, true);
    }
    /**
     * select
     * sets the SELECT part of the query
     *
     * @param string $select        Query SELECT part
     * @return Doctrine_Query
     */
    public function select($select)
    {
        return $this->parseQueryPart('select', $select);
    }
    /**
     * distinct
     * Makes the query SELECT DISTINCT.
     *
     * @param bool $flag            Whether or not the SELECT is DISTINCT (default true).
     * @return Doctrine_Query
     */
    public function distinct($flag = true)
    {   
        $this->parts['distinct'] = (bool) $flag;

        return $this;
    }

    /**
     * forUpdate
     * Makes the query SELECT FOR UPDATE.
     *
     * @param bool $flag            Whether or not the SELECT is FOR UPDATE (default true).
     * @return Doctrine_Query
     */
    public function forUpdate($flag = true)
    {
        $this->parts[self::FOR_UPDATE] = (bool) $flag;

        return $this;
    }
    /**
     * delete
     * sets the query type to DELETE
     *
     * @return Doctrine_Query
     */
    public function delete()
    {
        $this->type = self::DELETE;

        return $this;
    }
    /**
     * update
     * sets the UPDATE part of the query
     *
     * @param string $update        Query UPDATE part
     * @return Doctrine_Query
     */
    public function update($update)
    {
        $this->type = self::UPDATE;

        return $this->parseQueryPart('from', $update);
    }
    /**
     * set
     * sets the SET part of the query
     *
     * @param string $update        Query UPDATE part
     * @return Doctrine_Query
     */
    public function set($key, $value, $params = null)
    {
        if (is_array($key)) {
            foreach ($key as $k => $v) {
                $this->set($k, '?', array($v));                               
            }
        } else {
            if ($params !== null) {
                if (is_array($params)) {
                    $this->_params['set'] = array_merge($this->_params['set'], $params);
                } else {
                    $this->_params['set'][] = $params;
                }
            }
            return $this->parseQueryPart('set', $key . ' = ' . $value, true);
        }
    }
    /**
     * from
     * sets the FROM part of the query
     *
     * @param string $from          Query FROM part
     * @return Doctrine_Query
     */
    public function from($from)
    {
        return $this->parseQueryPart('from', $from);
    }
    /**
     * innerJoin
     * appends an INNER JOIN to the FROM part of the query
     *
     * @param string $join         Query INNER JOIN
     * @return Doctrine_Query
     */
    public function innerJoin($join)
    {
        return $this->parseQueryPart('from', 'INNER JOIN ' . $join, true);
    }
    /**
     * leftJoin
     * appends a LEFT JOIN to the FROM part of the query
     *
     * @param string $join         Query LEFT JOIN
     * @return Doctrine_Query
     */
    public function leftJoin($join)
    {
        return $this->parseQueryPart('from', 'LEFT JOIN ' . $join, true);
    }
    /**
     * groupBy
     * sets the GROUP BY part of the query
     *
     * @param string $groupby      Query GROUP BY part
     * @return Doctrine_Query
     */
    public function groupBy($groupby)
    {
        return $this->parseQueryPart('groupby', $groupby);
    }
    /**
     * where
     * sets the WHERE part of the query
     *
     * @param string $join         Query WHERE part
     * @param mixed $params        an array of parameters or a simple scalar
     * @return Doctrine_Query
     */
    public function where($where, $params = array())
    {
        $this->_params['where'] = array();
        if (is_array($params)) {
            $this->_params['where'] = $params;
        } else {
            $this->_params['where'][] = $params;
        }

        return $this->parseQueryPart('where', $where);
    }
    /**
     * having
     * sets the HAVING part of the query
     *
     * @param string $having       Query HAVING part
     * @param mixed $params        an array of parameters or a simple scalar
     * @return Doctrine_Query
     */
    public function having($having, $params = array())
    {
        $this->_params['having'] = array();
        if (is_array($params)) {
            $this->_params['having'] = $params;
        } else {
            $this->_params['having'][] = $params;
        }
        
        return $this->parseQueryPart('having', $having);
    }
    /**
     * orderBy
     * sets the ORDER BY part of the query
     *
     * @param string $orderby      Query ORDER BY part
     * @return Doctrine_Query
     */
    public function orderBy($orderby)
    {
        return $this->parseQueryPart('orderby', $orderby);
    }
    /**
     * limit
     * sets the Query query limit
     *
     * @param integer $limit        limit to be used for limiting the query results
     * @return Doctrine_Query
     */
    public function limit($limit)
    {
        return $this->parseQueryPart('limit', $limit);
    }
    /**
     * offset
     * sets the Query query offset
     *
     * @param integer $offset       offset to be used for paginating the query
     * @return Doctrine_Query
     */
    public function offset($offset)
    {
        return $this->parseQueryPart('offset', $offset);
    }
    
    /**
      * parseQueryPart
      * parses given DQL query part
      *
      * @param string $queryPartName     the name of the query part
      * @param string $queryPart         query part to be parsed
      * @return Doctrine_Query           this object
      */
      public function parseQueryPart($queryPartName, $queryPart)
      {
          $this->parts[$queryPartName][] = $queryPart;
          
          return $this;
      }
}