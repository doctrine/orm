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

/**
 * Doctrine_Hydrator_ArrayDriver
 * Defines an array fetching strategy.
 *
 * @package     Doctrine
 * @subpackage  Hydrate
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.phpdoctrine.org
 * @since       1.0
 * @version     $Revision$
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 */
class Doctrine_Hydrator_ArrayDriver
{
    /**
     *
     */
    public function getElementCollection($component)
    {
        return array();
    }
    
    /**
     *
     */
    public function getElement(array $data, $component)
    {
        return $data;
    }
    
    /** 
     *
     */
    /*public function isIdentifiable(array $data, Doctrine_Table $table)
    {
        return ( ! empty($data));
    }*/
    
    /**
     *
     */
    public function registerCollection($coll)
    { /* Nothing to do */ }
    
    /**
     *
     */
    public function initRelated(array &$data, $name)
    {
        if ( ! isset($data[$name])) {
            $data[$name] = array();
        }
        
        return true;
    }
    
    /**
     *
     */
    public function getNullPointer() 
    {
        return null;    
    }
    
    /**
     *
     */
    public function getLastKey(&$data)
    {
        end($data);
        
        return key($data);
    }
    
    /**
     *
     */
    public function flush()
    { /* Nothing to do */ }
}
