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
 * @author Roman Borschel <roman@code-factory.org>
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
            'quoteIdentifiers' => false,
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
    
    /**
     * Creates a new configuration that can be used for Doctrine.
     */
    public function __construct()
    {
        $this->_nullObject = Doctrine_Null::$INSTANCE;
        $this->_initAttributes();
    }
    
    /**
     * Initializes the attributes.
     * 
     * @return void
     */
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
    
    /**
     * Gets the value of a configuration attribute.
     *
     * @param string $name
     * @return mixed
     */
    public function get($name)
    {
        if ( ! $this->has($name)) {
            throw Doctrine_Configuration_Exception::unknownAttribute($name);
        }
        if ($this->_attributes[$name] === $this->_nullObject) {
            return null;
        }
        return $this->_attributes[$name];
    }
    
    /**
     * Sets the value of a configuration attribute.
     *
     * @param string $name
     * @param mixed $value
     */
    public function set($name, $value)
    {
        if ( ! $this->has($name)) {
            throw Doctrine_Configuration_Exception::unknownAttribute($name);
        }
        // TODO: do some value checking depending on the attribute
        $this->_attributes[$name] = $value;
    }
    
    /**
     * Checks whether the configuration contains/supports an attribute.
     *
     * @param string $name
     * @return boolean
     */
    public function has($name)
    {
        return isset($this->_attributes[$name]);
    }
}