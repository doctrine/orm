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

namespace Doctrine\ORM;

use Doctrine\ORM\Mapping\AssociationMapping;

/**
 * Represents a virtual proxy that is used for lazy to-one associations.
 *
 * @author robo
 * @since 2.0
 */
class VirtualProxy
{
    private $_assoc;
    private $_refProp;
    private $_owner;

    /**
     * Initializes a new VirtualProxy instance that will proxy the specified property on
     * the specified owner entity. The given association is used to lazy-load the
     * real object on access of the proxy.
     *
     * @param <type> $owner
     * @param <type> $assoc
     * @param <type> $refProp
     */
    public function __construct($owner, AssociationMapping $assoc, \ReflectionProperty $refProp)
    {
        $this->_owner = $owner;
        $this->_assoc = $assoc;
        $this->_refProp = $refProp;
    }

    private function _load()
    {
        $realInstance = $tis->_assoc->lazyLoadFor($this->_owner);
        $this->_refProp->setValue($this->_owner, $realInstance);
        return $realInstance;
    }

    /** All the "magic" interceptors */

    public function __call($method, $args)
    {
        $realInstance = $this->_load();
        return call_user_func_array(array($realInstance, $method), $args);
    }

    public function __get($prop)
    {
        $realInstance = $this->_load();
        return $realInstance->$prop;
    }

    public function __set($prop, $value)
    {
        $realInstance = $this->_load();
        $realInstance->$prop = $value;
    }

    public function __isset($prop)
    {
        $realInstance = $this->_load();
        return isset($realInstance->$prop);
    }

    public function __unset($prop)
    {
        $realInstance = $this->_load();
        unset($realInstance->$prop);
    }
}