<?php
require_once('spyc.php');

/*
 *  $Id: Yml.php 1080 2007-02-10 18:17:08Z jwage $
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
 * Doctrine_Parser_Yml
 *
 * @package     Doctrine
 * @subpackage  Parser
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.phpdoctrine.org
 * @since       1.0
 * @version     $Revision: 1080 $
 * @author      Jonathan H. Wage <jwage@mac.com>
 */
class Doctrine_Parser_Yml extends Doctrine_Parser
{
    /**
     * dumpData
     *
     * Dump an array of data to a specified path or return
     * 
     * @param  string $array Array of data to dump to yaml
     * @param  string $path  Path to dump the yaml to
     * @return string $yaml
     * @return void
     */
    public function dumpData($array, $path = null)
    {
        $spyc = new Doctrine_Spyc();
        
        $data = $spyc->dump($array, false, false);
        
        return $this->doDump($data, $path);
    }

    /**
     * loadData
     *
     * Load and parse data from a yml file
     * 
     * @param  string  $path  Path to load yaml data from
     * @return array   $array Array of parsed yaml data
     */
    public function loadData($path)
    {
        $contents = $this->doLoad($path);

        $spyc = new Doctrine_Spyc();
        
        $array = $spyc->load($contents);
        
        return $array;
    }
}