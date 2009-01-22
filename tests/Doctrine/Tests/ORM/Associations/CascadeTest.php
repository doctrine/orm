<?php
require_once 'lib/DoctrineTestInit.php';
 
class Orm_Associations_CascadeTest extends Doctrine_OrmTestCase
{    
    protected function setUp() {
        ;
    }
    
    protected function tearDown() {
        ;
    }
    
    public function testDeleteCascade()
    {
        $container = array();
        $cascade = new DeleteCascade();
        $cascade->cascade($entity, $container);
    }
    
    
}

abstract class Cascade
{     
    public function cascade(Doctrine_Entity $record, array &$container)
    {
        if ($this->shouldCascadeTo($record)) {
            $container[$record->getOid()] = $record;
        }        
        
        foreach ($record->getTable()->getRelations() as $relation) {
            if ($this->doCascade($relation)) {
                $this->prepareCascade($record, $relation);
                $relatedObjects = $record->get($relation->getAlias());
                if ($relatedObjects instanceof Doctrine_Record && $this->shouldCascadeTo($relatedObjects)
                       && ! isset($container[$relatedObjects->getOid()])) {
                    $this->cascade($relatedObjects, $container);
                } else if ($relatedObjects instanceof Doctrine_Collection && count($relatedObjects) > 0) {
                    foreach ($relatedObjects as $object) {
                        if ( ! isset($container[$object->getOid()])) {
                            $this->cascade($object, $container);
                        }
                    }
                }
            }
        }
    }
}

class DeleteCascade extends Cascade
{
    public function doCascade($relation)
    {
        return $relation->isCascadeDelete();    
    }
    
    public function prepareCascade($record, $relation)
    {
        $fieldName = $relation->getAlias();
        // if it's a xToOne relation and the related object is already loaded
        // we don't need to refresh, else we need to.
        if ( ! ($relation->getType() == Doctrine_Relation::ONE && isset($record->$fieldName))) {
            $record->refreshRelated($relation->getAlias());
        }
    }
    
    public function shouldCascadeTo(Doctrine_Entity $entity)
    {
        //TODO: also ignore removed Entities. incorporate that in exists() with a new
        // state? (DELETED?)
        return ! $entity->exists();
    }
}