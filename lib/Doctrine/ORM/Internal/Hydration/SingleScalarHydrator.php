<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of SingleScalarHydrator
 *
 * @author robo
 */
class Doctrine_ORM_Internal_Hydration_SingleScalarHydrator extends Doctrine_ORM_Internal_Hydration_AbstractHydrator
{
    /** @override */
    protected function _hydrateAll($parserResult)
    {
        $cache = array();
        $result = $this->_stmt->fetchAll(PDO::FETCH_ASSOC);
        //TODO: Let this exception be raised by Query as QueryException
        if (count($result) > 1 || count($result[0]) > 1) {
            throw Doctrine_ORM_Exceptions_HydrationException::nonUniqueResult();
        }
        $result = $this->_gatherScalarRowData($result[0], $cache);
        return array_shift($result);
    }

    /** {@inheritdoc} */
    protected function _getRowContainer()
    {
        return array();
    }
}

