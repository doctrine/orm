<?php
/*
 *  $Id: Parser.php 1080 2007-02-10 18:17:08Z jwage $
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
 * Doctrine_Parser
 *
 * @package     Doctrine
 * @subpackage  Parser
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision: 1080 $
 * @author      Jonathan H. Wage <jwage@mac.com>
 */
abstract class Doctrine_Parser
{
    /**
     * loadData
     *
     * Override in the parser driver
     *
     * @param string $array 
     * @return void
     * @author Jonathan H. Wage
     */
    abstract public function loadData($array);
    /**
     * dumpData
     *
     * Override in the praser driver
     *
     * @param string $array 
     * @param string $path 
     * @return void
     * @author Jonathan H. Wage
     */
    abstract public function dumpData($array, $path = null);
    /**
     * getParser
     *
     * Get instance of the specified parser
     *
     * @param string $type 
     * @return void
     * @author Jonathan H. Wage
     */
    static public function getParser($type)
    {
        $class = 'Doctrine_Parser_'.ucfirst($type);
        
        return new $class;
    }
    /**
     * load
     *
     * Interface for loading and parsing data from a file
     *
     * @param string $path 
     * @param string $type 
     * @return void
     * @author Jonathan H. Wage
     */
    static public function load($path, $type = 'xml')
    {
        $parser = self::getParser($type);
        
        return $parser->loadData($path);
    }
    /**
     * dump
     *
     * Interface for pulling and dumping data to a file
     *
     * @param string $array 
     * @param string $path 
     * @param string $type 
     * @return void
     * @author Jonathan H. Wage
     */
    static public function dump($array, $type = 'xml', $path = null)
    {
        $parser = self::getParser($type);
        
        return $parser->dumpData($array, $path);
    }
}