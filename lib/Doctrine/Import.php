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
 * @package     Doctrine
 * @url         http://www.phpdoctrine.com
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @author      Jukka Hassinen <Jukka.Hassinen@BrainAlliance.com>
 * @version     $Id$
 */



/**
 * class Doctrine_Import
 * Main responsible of performing import operation. Delegates database schema
 * reading to a reader object and passes the result to a builder object which
 * builds a Doctrine data model.
 */
class Doctrine_Import {
    /**
     * @var Doctrine_Import_Reader $reader
     */
    private $reader;
    /**
     * @var Doctrine_Import_Builder $builder
     */
    private $builder;


    /**
     *
     * @return 
     * @access public
     */
    public function import( ) {
        
    }
    /**
     *
     * @param Doctrine_Import_Reader reader      
     * @return void
     */
    public function setReader(Doctrine_Import_Reader $reader) {
        $this->reader = $reader;
    }
    /**
     *
     * @param Doctrine_Import_Builder builder      
     * @return void
     */
    public function setBuilder(Doctrine_Import_Builder $builder) {
        $this->builder = $builder;
    }
}
