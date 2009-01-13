<?php

/**
 * Hydrator that produces flat, rectangular results of scalar data.
 * The created result is almost the same as a regular SQL result set, except
 * that column names are mapped to field names and data type conversions.
 *
 * @author robo
 * @since 2.0
 */
class Doctrine_ORM_Internal_Hydration_ScalarHydrator extends Doctrine_ORM_Internal_Hydration_AbstractHydrator
{
    /** @override */
    protected function _hydrateAll($parserResult)
    {
        $result = array();
        $cache = array();
        while ($data = $this->_stmt->fetch(PDO::FETCH_ASSOC)) {
            $result[] = $this->_gatherScalarRowData($data, $cache);
        }
        return $result;
    }

    /** @override */
    protected function _hydrateRow(array &$data, array &$cache, &$result)
    {
        $result[] = $this->_gatherScalarRowData($data, $cache);
    }

    /** @override */
    protected function _getRowContainer()
    {
        return array();
    }
}

