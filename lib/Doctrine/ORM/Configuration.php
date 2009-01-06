<?php 
/*
 *  $Id$
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the LGPL. For more information, see
 * <http://www.phpdoctrine.org>.
 */

#namespace Doctrine\ORM;

#use Doctrine\DBAL\Configuration;
#use Doctrine\ORM\Mapping\Driver\AnnotationDriver;

/**
 * Configuration container for all configuration options of Doctrine.
 * It combines all configuration options from DBAL & ORM.
 * 
 * INTERNAL: When adding a new configuration option just write a getter/setter
 * pair and add the option to the _attributes array with a proper default value.
 *
 * @author Roman Borschel <roman@code-factory.org>
 * @since 2.0
 */
class Doctrine_ORM_Configuration extends Doctrine_DBAL_Configuration
{    
    /**
     * Creates a new configuration that can be used for Doctrine.
     */
    public function __construct()
    {
        $this->_attributes = array_merge($this->_attributes, array(
            'resultCacheImpl' => null,
            'queryCacheImpl' => null,
            'metadataCacheImpl' => null,
            'metadataDriverImpl' => new Doctrine_ORM_Mapping_Driver_AnnotationDriver()
            ));
    }

    public function setMetadataDriverImpl($driverImpl)
    {
        $this->_attributes['metadataDriverImpl'] = $driverImpl;
    }

    public function getMetadataDriverImpl()
    {
        return $this->_attributes['metadataDriverImpl'];
    }
    
    public function getResultCacheImpl()
    {
        return $this->_attributes['resultCacheImpl'];
    }
    
    public function setResultCacheImpl($cacheImpl)
    {
        $this->_attributes['resultCacheImpl'] = $cacheImpl;
    }
    
    public function getQueryCacheImpl()
    {
        return $this->_attributes['queryCacheImpl'];
    }
    
    public function setQueryCacheImpl($cacheImpl)
    {
        $this->_attributes['queryCacheImpl'] = $cacheImpl;
    }
    
    public function getMetadataCacheImpl()
    {
        return $this->_attributes['metadataCacheImpl'];
    }
    
    public function setMetadataCacheImpl($cacheImpl)
    {
        $this->_attributes['metadataCacheImpl'] = $cacheImpl;
    }
}