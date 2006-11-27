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
 * Doctrine_Hydrate_Alias
 * This class handles the creation of aliases for components in DQL query
 *
 * @package     Doctrine
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision$
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 */
class Doctrine_Hydrate_Alias {
    
    protected $shortAliases      = array();
    
    protected $shortAliasIndexes = array();

    public function clear() {
        $this->shortAliases = array();
        $this->shortAliasIndexes = array();
    }

    public function generateNewAlias($alias) {
        if(isset($this->shortAliases[$alias])) {
            // generate a new alias
            $name = substr($alias, 0, 1);
            $i    = ((int) substr($alias, 1));

            if($i == 0)
                $i = 1;

            $newIndex  = ($this->shortAliasIndexes[$name] + $i);

            return $name . $newIndex;
        }

        return $alias;
    }

    public function hasAliasFor($tableName) {
        return (isset($this->shortAliases[$tableName]));
    }
    public function getShortAliasIndex($alias) {
        if( ! isset($this->shortAliasIndexes[$alias]))
            return 0;
        
        return $this->shortAliasIndexes[$alias];
    }
    public function generateShortAlias($tableName) {
        $char   = strtolower(substr($tableName, 0, 1));

        $alias  = $char;

        if( ! isset($this->shortAliasIndexes[$alias]))
            $this->shortAliasIndexes[$alias] = 1;

        while(isset($this->shortAliases[$alias])) {
            $alias = $char . ++$this->shortAliasIndexes[$alias];
        }
        $this->shortAliases[$alias] = $tableName;

        return $alias;
    }

    public function getShortAlias($tableName) {
        $alias = array_search($tableName, $this->shortAliases);

        if($alias !== false)
            return $alias;
        
        return $this->generateShortAlias($tableName);
    }
}
