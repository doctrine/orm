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
 * <http://www.phpdoctrine.com>.
 */

/**
 * Doctrine_Hydrate_Array
 * defines an array fetching strategy for Doctrine_Hydrate
 *
 * @package     Doctrine
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision$
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 */
class Doctrine_Hydrate_Array
{
    public function getElementCollection($component)
    {
        return array();
    }
    public function getElement(array $data, $component)
    {
        return $data;
    }
    public function isIdentifiable(array $data, Doctrine_Table $table)
    {
        return ( ! empty($data));
    }
    public function registerCollection($coll)
    {

    }
    public function initRelated(array &$data, $name)
    {
    	if ( ! isset($data[$name])) {
            $data[$name] = array();
        }
        return true;
    }
    public function getNullPointer() 
    {
        return null;	
    }
    public function search(array $element, array $data)
    {
        foreach ($data as $key => $val) {
            $found = true;
            foreach ($element as $k => $e) {
                if ($val[$k] !== $e) {
                    $found = false;
                }
            }
            if ($found) {
                return $key;
            }
        }
        return false;
    }
    public function flush()
    {
    	
    }
}
