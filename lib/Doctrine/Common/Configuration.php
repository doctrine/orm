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

#namespace Doctrine::Common;

#use Doctrine::Common::NullObject;

/**
 * The Configuration is the container for all configuration options of Doctrine.
 * It combines all configuration options from DBAL & ORM.
 * 
 * INTERNAL: When adding a new configuration option just write a getter/setter
 * pair and add the option to the _attributes array with a proper default value.
 *
 * @author Roman Borschel <roman@code-factory.org>
 * @since 2.0
 */
class Doctrine_Common_Configuration
{
    /**
     * The attributes that are contained in the configuration.
     * Values are default values.
     *
     * @var array
     */
    private $_attributes = array(
            'quoteIdentifiers' => false,
            'indexNameFormat' => '%s_idx',
            'sequenceNameFormat' => '%s_seq',
            'tableNameFormat' => '%s',
            'resultCacheImpl' => null,
            'queryCacheImpl' => null,
            'metadataCacheImpl' => null,
        );
    
    /**
     * Creates a new configuration that can be used for Doctrine.
     */
    public function __construct()
    {}
    
    public function getQuoteIdentifiers()
    {
        return $this->_attributes['quoteIdentifiers'];
    }
    
    public function setQuoteIdentifiers($bool)
    {
        $this->_attributes['quoteIdentifiers'] = (bool)$bool;
    }
    
    public function getIndexNameFormat()
    {
        return $this->_attributes['indexNameFormat'];
    }
    
    public function setIndexNameFormat($format)
    {
        //TODO: check format?
        $this->_attributes['indexNameFormat'] = $format;
    }
    
    public function getSequenceNameFormat()
    {
        return $this->_attributes['sequenceNameFormat'];
    }
    
    public function setSequenceNameFormat($format)
    {
        //TODO: check format?
        $this->_attributes['sequenceNameFormat'] = $format;
    }
    
    public function getTableNameFormat()
    {
        return $this->_attributes['tableNameFormat'];
    }
    
    public function setTableNameFormat($format)
    {
        //TODO: check format?
        $this->_attributes['tableNameFormat'] = $format;
    }
    
    public function getResultCacheImpl()
    {
        return $this->_attributes['resultCacheImpl'];
    }
    
    public function setResultCacheImpl(Doctrine_Cache_Interface $cacheImpl)
    {
        $this->_attributes['resultCacheImpl'] = $cacheImpl;
    }
    
    public function getQueryCacheImpl()
    {
        return $this->_attributes['queryCacheImpl'];
    }
    
    public function setQueryCacheImpl(Doctrine_Cache_Interface $cacheImpl)
    {
        $this->_attributes['queryCacheImpl'] = $cacheImpl;
    }
    
    public function getMetadataCacheImpl()
    {
        return $this->_attributes['metadataCacheImpl'];
    }
    
    public function setMetadataCacheImpl(Doctrine_Cache_Interface $cacheImpl)
    {
        $this->_attributes['metadataCacheImpl'] = $cacheImpl;
    }
    
    public function setCustomTypes(array $types)
    {
        foreach ($types as $name => $typeClassName) {
            Doctrine_DataType::addCustomType($name, $typeClassName);
        }
    }
    
    public function setTypeOverrides(array $overrides)
    {
        foreach ($override as $name => $typeClassName) {
            Doctrine_DBAL_Types_Type::overrideType($name, $typeClassName);
        }
    }
}