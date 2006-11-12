<?php
Doctrine::autoload('Doctrine_Collection');
/**
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @package     Doctrine
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @version     $Revision$
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 */
class Doctrine_Collection_Immediate extends Doctrine_Collection {
    /**
     * @param Doctrine_DQL_Parser $graph
     * @param integer $key              
     */
    public function __construct(Doctrine_Table $table) {
        parent::__construct($table);
    }
}

