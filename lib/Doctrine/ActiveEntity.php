<?php

#namespace Doctrine::ORM;

/**
 * The ActiveEntity class adds an ActiveRecord-like interface to Entity classes
 * that allows the Entities to directly interact with the persistence layer.
 * This is mostly just a convenient wrapper for people who want it that forwards
 * most method calls to the EntityManager.
 *
 * @since 2.0
 */
class Doctrine_ActiveEntity extends Doctrine_Entity
{
    /**
     * Saves the current state of the entity into the database.
     *
     * @param Doctrine_Connection $conn                 optional connection parameter
     * @return void
     * @todo ActiveEntity method.
     */
    public function save()
    {
        // TODO: Forward to EntityManager. There: registerNew() OR registerDirty() on UnitOfWork.
        $this->_em->save($this);
    }
    
    /**
     * Execute a SQL REPLACE query. A REPLACE query is identical to a INSERT
     * query, except that if there is already a row in the table with the same
     * key field values, the REPLACE query just updates its values instead of
     * inserting a new row.
     *
     * The REPLACE type of query does not make part of the SQL standards. Since
     * practically only MySQL and SQLIte implement it natively, this type of
     * query isemulated through this method for other DBMS using standard types
     * of queries inside a transaction to assure the atomicity of the operation.
     *
     * @param Doctrine_Connection $conn             optional connection parameter
     * @throws Doctrine_Connection_Exception        if some of the key values was null
     * @throws Doctrine_Connection_Exception        if there were no key fields
     * @throws Doctrine_Connection_Exception        if something fails at database level
     * @return integer                              number of rows affected
     * @todo ActiveEntity method.
     */
    public function replace()
    {
        return $this->_em->replace(
                $this->_class,
                $this->getPrepared(),
                $this->_id);
    }
    
    /**
     * Creates an array representation of the object's data.
     *
     * @param boolean $deep - Return also the relations
     * @return array
     * @todo ActiveEntity method.
     * @todo Move implementation to EntityManager.
     */
    public function toArray($deep = true, $prefixKey = false)
    {
        $a = array();

        foreach ($this as $column => $value) {
            if ($value === Doctrine_Null::$INSTANCE || is_object($value)) {
                $value = null;
            }
            $a[$column] = $value;
        }

        if ($this->_class->getIdentifierType() == Doctrine::IDENTIFIER_AUTOINC) {
            $idFieldNames = $this->_class->getIdentifier();
            $idFieldName = $idFieldNames[0];
            
            $ids = $this->identifier();
            $id = count($ids) > 0 ? array_pop($ids) : null;
            
            $a[$idFieldName] = $id;
        }

        if ($deep) {
            foreach ($this->_references as $key => $relation) {
                if ( ! $relation instanceof Doctrine_Null) {
                    $a[$key] = $relation->toArray($deep, $prefixKey);
                }
            }
        }

        // [FIX] Prevent mapped Doctrine_Entitys from being displayed fully
        foreach ($this->_values as $key => $value) {
            if ($value instanceof Doctrine_Entity) {
                $a[$key] = $value->toArray($deep, $prefixKey);
            } else {
                $a[$key] = $value;
            }
        }

        return $a;
    }
    
    /**
     * Merges this Entity with an array of values
     * or with another existing instance of.
     *
     * @param  mixed $data Data to merge. Either another instance of this model or an array
     * @param  bool  $deep Bool value for whether or not to merge the data deep
     * @return void
     * @todo ActiveEntity method.
     * @todo Move implementation to EntityManager.
     */
    public function merge($data, $deep = true)
    {
        if ($data instanceof $this) {
            $array = $data->toArray($deep);
        } else if (is_array($data)) {
            $array = $data;
        } else {
            $array = array();
        }

        return $this->fromArray($array, $deep);
    }
    
    /**
     * fromArray
     *
     * @param   string $array
     * @param   bool  $deep Bool value for whether or not to merge the data deep
     * @return  void
     * @todo ActiveEntity method.
     * @todo Move implementation to EntityManager.
     */
    public function fromArray($array, $deep = true)
    {
        if (is_array($array)) {
            foreach ($array as $key => $value) {
                if ($deep && $this->getTable()->hasRelation($key)) {
                    $this->$key->fromArray($value, $deep);
                } else if ($this->getTable()->hasField($key)) {
                    $this->set($key, $value);
                }
            }
        }
    }
    
    /**
     * Synchronizes a Doctrine_Entity and its relations with data from an array
     *
     * It expects an array representation of a Doctrine_Entity similar to the return
     * value of the toArray() method. If the array contains relations it will create
     * those that don't exist, update the ones that do, and delete the ones missing
     * on the array but available on the Doctrine_Entity
     *
     * @param array $array representation of a Doctrine_Entity
     * @todo ActiveEntity method.
     * @todo Move implementation to EntityManager.
     */
    public function synchronizeFromArray(array $array)
    {
        foreach ($array as $key => $value) {
            if ($this->getTable()->hasRelation($key)) {
                $this->get($key)->synchronizeFromArray($value);
            } else if ($this->getTable()->hasColumn($key)) {
                $this->set($key, $value);
            }
        }
        // eliminate relationships missing in the $array
        foreach ($this->_references as $name => $obj) {
            if ( ! isset($array[$name])) {
                unset($this->$name);
            }
        }
    }
    
   /**
     * exportTo
     *
     * @param string $type
     * @param string $deep
     * @return void
     * @todo ActiveEntity method.
     */
    public function exportTo($type, $deep = true)
    {
        if ($type == 'array') {
            return $this->toArray($deep);
        } else {
            return Doctrine_Parser::dump($this->toArray($deep, true), $type);
        }
    }

    /**
     * importFrom
     *
     * @param string $type
     * @param string $data
     * @return void
     * @author Jonathan H. Wage
     * @todo ActiveEntity method.
     */
    public function importFrom($type, $data)
    {
        if ($type == 'array') {
            return $this->fromArray($data);
        } else {
            return $this->fromArray(Doctrine_Parser::load($data, $type));
        }
    }
    
    /**
     * Deletes the persistent state of the entity.
     *
     * @return boolean  TRUE on success, FALSE on failure.
     * @todo ActiveRecord method.
     */
    public function delete()
    {
        // TODO: Forward to EntityManager. There: registerRemoved() on UnitOfWork
        return $this->_em->remove($this);
    }
    
    /**
     * Creates a copy of the entity.
     *
     * @return Doctrine_Entity
     * @todo ActiveEntity method. Implementation to EntityManager.
     */
    public function copy($deep = true)
    {
        $data = $this->_data;

        if ($this->_class->getIdentifierType() === Doctrine::IDENTIFIER_AUTOINC) {
            $idFieldNames = (array)$this->_class->getIdentifier();
            $id = $idFieldNames[0];
            unset($data[$id]);
        }

        $ret = $this->_em->createEntity($this->_entityName, $data);
        $modified = array();

        foreach ($data as $key => $val) {
            if ( ! ($val instanceof Doctrine_Null)) {
                $ret->_modified[] = $key;
            }
        }

        if ($deep) {
            foreach ($this->_references as $key => $value) {
                if ($value instanceof Doctrine_Collection) {
                    foreach ($value as $record) {
                        $ret->{$key}[] = $record->copy($deep);
                    }
                } else {
                    $ret->set($key, $value->copy($deep));
                }
            }
        }

        return $ret;
    }
    
    /**
     * Removes links from this record to given records
     * if no ids are given, it removes all links
     *
     * @param string $alias     related component alias
     * @param array $ids        the identifiers of the related records
     * @return Doctrine_Entity  this object
     * @todo ActiveEntity method.
     */
    public function unlink($alias, $ids = array())
    {
        $ids = (array) $ids;

        $q = new Doctrine_Query();

        $rel = $this->getTable()->getRelation($alias);

        if ($rel instanceof Doctrine_Relation_Association) {
            $q->delete()
              ->from($rel->getAssociationTable()->getComponentName())
              ->where($rel->getLocal() . ' = ?', array_values($this->identifier()));

            if (count($ids) > 0) {
                $q->whereIn($rel->getForeign(), $ids);
            }

            $q->execute();

        } else if ($rel instanceof Doctrine_Relation_ForeignKey) {
            $q->update($rel->getTable()->getComponentName())
              ->set($rel->getForeign(), '?', array(null))
              ->addWhere($rel->getForeign() . ' = ?', array_values($this->identifier()));

            if (count($ids) > 0) {
                $relTableIdFieldNames = (array)$rel->getTable()->getIdentifier();
                $q->whereIn($relTableIdFieldNames[0], $ids);
            }

            $q->execute();
        }
        if (isset($this->_references[$alias])) {
            foreach ($this->_references[$alias] as $k => $record) {
                
                if (in_array(current($record->identifier()), $ids)) {
                    $this->_references[$alias]->remove($k);
                }
                
            }
            
            $this->_references[$alias]->takeSnapshot();
        }
        return $this;
    }


    /**
     * Creates links from this record to given records.
     *
     * @param string $alias     related component alias
     * @param array $ids        the identifiers of the related records
     * @return Doctrine_Entity  this object
     * @todo ActiveEntity method.
     */
    public function link($alias, array $ids)
    {
        if ( ! count($ids)) {
            return $this;
        }

        $identifier = array_values($this->identifier());
        $identifier = array_shift($identifier);

        $rel = $this->getTable()->getRelation($alias);

        if ($rel instanceof Doctrine_Relation_Association) {
            $modelClassName = $rel->getAssociationTable()->getComponentName();
            $localFieldName = $rel->getLocalFieldName();
            $localFieldDef  = $rel->getAssociationTable()->getColumnDefinition($localFieldName);
            if ($localFieldDef['type'] == 'integer') {
                $identifier = (integer) $identifier;
            }
            $foreignFieldName = $rel->getForeignFieldName();
            $foreignFieldDef  = $rel->getAssociationTable()->getColumnDefinition($foreignFieldName);
            if ($foreignFieldDef['type'] == 'integer') {
                for ($i = 0; $i < count($ids); $i++) {
                    $ids[$i] = (integer) $ids[$i];
                }
            }
            foreach ($ids as $id) {
                $record = new $modelClassName;
                $record[$localFieldName]   = $identifier;
                $record[$foreignFieldName] = $id;
                $record->save();
            }

        } else if ($rel instanceof Doctrine_Relation_ForeignKey) {

            $q = new Doctrine_Query();

            $q->update($rel->getTable()->getComponentName())
              ->set($rel->getForeign(), '?', array_values($this->identifier()));

            if (count($ids) > 0) {
                $relTableIdFieldNames = (array)$rel->getTable()->getIdentifier();
                $q->whereIn($relTableIdFieldNames[0], $ids);
            }

            $q->execute();

        } else if ($rel instanceof Doctrine_Relation_LocalKey) {
            $q = new Doctrine_Query();
            $q->update($this->getTable()->getComponentName())
                    ->set($rel->getLocalFieldName(), '?', $ids);

            if (count($ids) > 0) {
                $relTableIdFieldNames = (array)$rel->getTable()->getIdentifier();
                $q->whereIn($relTableIdFieldNames[0], array_values($this->identifier()));
            }

            $q->execute();

        }

        return $this;
    }
    
   /**
     * Refresh internal data from the database
     *
     * @param bool $deep                        If true, fetch also current relations. Caution: this deletes
     *                                          any aggregated values you may have queried beforee
     *
     * @throws Doctrine_Record_Exception        When the refresh operation fails (when the database row
     *                                          this record represents does not exist anymore)
     * @return boolean
     * @todo Implementation to EntityManager.
     * @todo Move to ActiveEntity (extends Entity). Implementation to EntityManager.
     */
    public function refresh($deep = false)
    {
        $id = $this->identifier();
        if ( ! is_array($id)) {
            $id = array($id);
        }
        if (empty($id)) {
            return false;
        }
        $id = array_values($id);

        if ($deep) {
            $query = $this->_em->createQuery()->from($this->_entityName);
            foreach (array_keys($this->_references) as $name) {
                $query->leftJoin(get_class($this) . '.' . $name);
            }
            $query->where(implode(' = ? AND ', $this->_class->getIdentifierColumnNames()) . ' = ?');
            $this->clearRelated();
            $record = $query->fetchOne($id);
        } else {
            // Use FETCH_ARRAY to avoid clearing object relations
            $record = $this->getRepository()->find($this->identifier(), Doctrine::HYDRATE_ARRAY);
            if ($record) {
                $this->hydrate($record);
            }
        }

        if ($record === false) {
            throw new Doctrine_Record_Exception('Failed to refresh. Record does not exist.');
        }

        $this->_modified = array();

        $this->_extractIdentifier();

        $this->_state = Doctrine_Entity::STATE_CLEAN;

        return $this;
    }
    
    /**
     * Hydrates this object from given array
     *
     * @param array $data
     * @return boolean
     */
    final public function hydrate(array $data)
    {
        $this->_data = array_merge($this->_data, $data);
        $this->_extractIdentifier();
    }
}

?>