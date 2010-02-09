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

namespace Doctrine\ORM\Query\Expr;

/**
 * Abstract base Expr class for building DQL parts
 *
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link    www.doctrine-project.org
 * @since   2.0
 * @version $Revision$
 * @author  Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author  Jonathan Wage <jonwage@gmail.com>
 * @author  Roman Borschel <roman@code-factory.org>
 */
abstract class Base
{
    protected $_preSeparator = '(';
    protected $_separator = ', ';
    protected $_postSeparator = ')';
    protected $_allowedClasses = array();

    private $_parts = array();

    public function __construct($args = array())
    {
        $this->addMultiple($args);
    }
    
    public function addMultiple($args = array())
    {
        foreach ((array) $args as $arg) {
            $this->add($arg);
        }
    }

    public function add($arg)
    {
        if ( ! empty($arg) || ($arg instanceof self && $arg->count() > 0)) {
            // If we decide to keep Expr\Base instances, we can use this check
            if ( ! is_string($arg)) {
                $class = get_class($arg);

                if ( ! in_array($class, $this->_allowedClasses)) {
                    throw new \InvalidArgumentException("Expression of type '$class' not allowed in this context.");
                }
            }

            $this->_parts[] = $arg;
        }
    }

    public function count()
    {
        return count($this->_parts);
    }

    public function __toString()
    {
        if ($this->count() == 1) {
            return (string) $this->_parts[0];
        }
        
        return $this->_preSeparator . implode($this->_separator, $this->_parts) . $this->_postSeparator;
    }
}