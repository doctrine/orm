<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Doctrine\Tests\Mocks;

/**
 * Description of Doctrine_MetadataDriverMock
 *
 * @author robo
 */
class MetadataDriverMock
{
    public function loadMetadataForClass($className, \Doctrine\ORM\Mapping\ClassMetadata $metadata) {
        return;
    }
    public function isTransient($className) {
        return false;
    }
}

