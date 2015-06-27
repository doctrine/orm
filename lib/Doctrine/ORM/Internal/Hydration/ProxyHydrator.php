<?php

namespace Doctrine\ORM\Internal\Hydration;

class ProxyHydrator extends SimpleObjectHydrator
{
    /**
     * {@inheritdoc}
     */
    protected function hydrateRowData(array $sqlResult, array &$result)
    {
        $entityName = $this->getEntityName($sqlResult);
        $identifier = array();

        foreach ($sqlResult as $column => $value) {
            // An ObjectHydrator should be used instead of SimpleObjectHydrator
            if (isset($this->_rsm->relationMap[$column])) {
                throw new \Exception(sprintf('Unable to retrieve association information for column "%s"', $column));
            }

            $cacheKeyInfo = $this->hydrateColumnInfo($column);

            if ( ! $cacheKeyInfo || ! $cacheKeyInfo['isIdentifier']) {
                continue;
            }

            // Convert field to a valid PHP value
            if (isset($cacheKeyInfo['type'])) {
                $type  = $cacheKeyInfo['type'];
                $value = $type->convertToPHPValue($value, $this->_platform);
            }

            $fieldName = $cacheKeyInfo['fieldName'];

            // Prevent overwrite in case of inherit classes using same property name (See AbstractHydrator)
            if ( ! isset($identifier[$fieldName]) || $value !== null) {
                $identifier[$fieldName] = $value;
            }
        }

        $result[] = $this->_em->getProxyFactory()->getProxy($entityName, $identifier);
    }
}
