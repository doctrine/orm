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

#namespace Doctrine::Common::Events;

/**
 * Doctrine_Event
 *
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @package     Doctrine
 * @subpackage  Event
 * @link        www.phpdoctrine.org
 * @since       2.0
 * @version     $Revision$
 */
class Doctrine_Common_Events_Event
{
    /* Event callback constants */
    const preDelete = 'preDelete';
    const postDelete = 'postDelete';
    //...more

    protected $_type;
    protected $_target;
    protected $_defaultPrevented;


    public function __construct($type, $target = null)
    {
        $this->_type = $type;
        $this->_target = $target;
        $this->_defaultPrevented = false;
    }


    public function getType()
    {
        return $this->_type;
    }


    public function preventDefault()
    {
        $this->_defaultPrevented = true;
    }


    public function getDefaultPrevented()
    {
        return $this->_defaultPrevented;
    }


    public function getTarget()
    {
        return $this->_target;
    }
}  
