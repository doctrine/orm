<?php

namespace Doctrine\ORM\Persisters;

class SingleTablePersister extends AbstractEntityPersister
{
    //private $_selectColumnList = array();

    public function insert($entity)
    {
        return parent::insert($entity);
    }

    /** @override */
    protected function _prepareData($entity, array &$result, $isInsert = false)
    {
        parent::_prepareData($entity, $result, $isInsert);
        // Populate the discriminator column
        if ($isInsert) {
            $discColumn = $this->_classMetadata->getDiscriminatorColumn();
            $discMap = $this->_classMetadata->getDiscriminatorMap();
            $result[$discColumn['name']] = array_search($this->_entityName, $discMap);
        }
    }

    /**
     * {@inheritdoc}
     */
    /*public function getAllFieldMappingsInHierarchy()
    {
        $fieldMappings = $this->_classMetadata->getFieldMappings();
        foreach ($this->_classMetadata->getSubclasses() as $subclassName) {
            $fieldMappings = array_merge(
                    $fieldMappings,
                    $this->_em->getClassMetadata($subclassName)->getFieldMappings()
                    );
        }
        return $fieldMappings;
    }*/
}