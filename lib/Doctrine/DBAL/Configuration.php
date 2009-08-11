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
 * <http://www.doctrine-project.org>.
 */

namespace Doctrine\DBAL;

use Doctrine\DBAL\Types\Type;

/**
 * Configuration container for the Doctrine DBAL.
 *
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link    www.doctrine-project.org
 * @since   2.0
 * @version $Revision: 3938 $
 * @author  Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author  Jonathan Wage <jonwage@gmail.com>
 * @author  Roman Borschel <roman@code-factory.org>
 *
 * @internal When adding a new configuration option just write a getter/setter
 *           pair and add the option to the _attributes array with a proper default value.
 */
class Configuration
{
    /**
     * The attributes that are contained in the configuration.
     * Values are default values.
     *
     * @var array
     */
    protected $_attributes = array();
    
    /**
     * Creates a new DBAL configuration instance.
     */
    public function __construct()
    {
        $this->_attributes = array(
            'sqlLogger' => null
        );
    }
    
    /**
     * Sets the SQL logger to use. Defaults to NULL which means SQL logging is disabled.
     *
     * @param SqlLogger $logger
     */
    public function setSqlLogger($logger)
    {
        $this->_attributes['sqlLogger'] = $logger;
    }
    
    /**
     * Gets the SQL logger that is used.
     * 
     * @return SqlLogger
     */
    public function getSqlLogger()
    {
        return $this->_attributes['sqlLogger'];
    }

    /**
     * Defines new custom types to be supported by Doctrine
     *
     * @param array $types Key-value map of types to include
     * @param boolean $override Optional flag to support only inclusion or also override
     * @throws DoctrineException
     */
    public function setCustomTypes(array $types, $override = false)
    {
        foreach ($types as $name => $typeClassName) {
            $method = (Type::hasType($name) ? 'override' : 'add') . 'Type';
            
            Type::$method($name, $typeClassName);
        }
    }

    /**
     * Overrides existent types in Doctrine
     *
     * @param array $types Key-value map of types to override
     * @throws DoctrineException
     */
    public function setTypeOverrides(array $overrides)
    {
        foreach ($override as $name => $typeClassName) {
            Type::overrideType($name, $typeClassName);
        }
    }
}