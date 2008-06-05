<?php 

#namespace Doctrine::Common;

#use Doctrine::Common::NullObject;

/**
 * The Configuration is the container for all configuration options of Doctrine.
 * It combines all configuration options from DBAL & ORM to make it easy for the
 * user.
 *
 * @since 2.0
 */
class Doctrine_Configuration
{
    private $_nullObject;
    
    /**
     * The attributes that are contained in the configuration.
     *
     * @var array
     */
    private $_attributes = array(
            'quoteIdentifier' => false,
            'indexNameFormat' => '%s_idx',
            'sequenceNameFormat' => '%s_seq',
            'tableNameFormat' => '%s',
            'resultCache' => null,
            'resultCacheLifeSpan' => null,
            'queryCache' => null,
            'queryCacheLifeSpan' => null,
            'metadataCache' => null,
            'metadataCacheLifeSpan' => null
        );
    
    public function __construct()
    {
        $this->_nullObject = Doctrine_Null::$INSTANCE;
        $this->_initAttributes();
    }
    
    private function _initAttributes()
    {
        // Change null default values to references to the Null object to allow
        // fast isset() checks instead of array_key_exists().
        foreach ($this->_attributes as $key => $value) {
            if ($value === null) {
                $this->_attributes[$key] = $this->_nullObject;
            }
        }
    }
    
    public function get($name)
    {
        if ( ! $this->hasAttribute($name)) {
            throw Doctrine_Configuration_Exception::unknownAttribute($name);
        }
        if ($this->_attributes[$name] === $this->_nullObject) {
            return null;
        }
        return $this->_attributes[$name];
    }
    
    public function set($name, $value)
    {
        if ( ! $this->hasAttribute($name)) {
            throw Doctrine_Configuration_Exception::unknownAttribute($name);
        }
        // TODO: do some value checking depending on the attribute
        $this->_attributes[$name] = $value;
    }
    
    public function has($name)
    {
        return isset($this->_attributes[$name]);
    }
}