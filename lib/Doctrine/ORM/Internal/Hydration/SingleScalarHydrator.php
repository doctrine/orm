<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Doctrine\ORM\Internal\Hydration;

use \PDO;

/**
 * Description of SingleScalarHydrator
 *
 * @author robo
 */
class SingleScalarHydrator extends AbstractHydrator
{
    /** @override */
    protected function _hydrateAll()
    {
        $cache = array();
        $result = $this->_stmt->fetchAll(PDO::FETCH_ASSOC);
        //TODO: Let this exception be raised by Query as QueryException
        if (count($result) > 1 || count($result[0]) > 1) {
            throw HydrationException::nonUniqueResult();
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

